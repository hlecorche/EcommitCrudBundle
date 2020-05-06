/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import * as ajax from '../../../../src/Resources/public/js/ajax';
import $ from 'jquery';

describe('Test Ajax.sendRequest', function () {
    beforeEach(function () {
        jasmine.Ajax.install();

        jasmine.Ajax.stubRequest('/goodRequest').andReturn({
            status: 200,
            responseText: 'OK'
        });

        jasmine.Ajax.stubRequest('/resultJS').andReturn({
            status: 200,
            responseText: '<div id="subcontent">BEFORE</div><script>document.getElementById("subcontent").innerHTML="AFTER"</script>'
        });

        jasmine.Ajax.stubRequest('/error404').andReturn({
            status: 404,
            responseText: 'Page not found !'
        });
    });

    afterEach(function () {
        jasmine.Ajax.uninstall();
        $('.html-test').remove();
    });

    it('Send request', function () {
        const callbackSuccess = jasmine.createSpy('success');
        const callbackError = jasmine.createSpy('error');
        const callbackComplete = jasmine.createSpy('complete');

        ajax.sendRequest({
            url: '/goodRequest',
            onComplete: function (args) {
                callbackComplete();
            },
            onSuccess: function (args) {
                callbackSuccess(args.data);
            },
            onError: function (args) {
                callbackError(args.jqXHR.responseText);
            }
        });

        expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest');
        expect(jasmine.Ajax.requests.mostRecent().method).toBe('POST');
        expect(callbackSuccess).toHaveBeenCalledWith('OK');
        expect(callbackError).not.toHaveBeenCalled();
        expect(callbackComplete).toHaveBeenCalled();
    });

    it('Send bad request', function () {
        const callbackSuccess = jasmine.createSpy('success');
        const callbackError = jasmine.createSpy('error');
        const callbackComplete = jasmine.createSpy('complete');

        ajax.sendRequest({
            url: '/error404',
            onComplete: function (args) {
                callbackComplete();
            },
            onSuccess: function (args) {
                callbackSuccess(args.data);
            },
            onError: function (args) {
                callbackError(args.jqXHR.responseText);
            }
        });

        expect(jasmine.Ajax.requests.mostRecent().url).toBe('/error404');
        expect(callbackSuccess).not.toHaveBeenCalled();
        expect(callbackError).toHaveBeenCalledWith('Page not found !');
        expect(callbackComplete).toHaveBeenCalled();
    });

    it('Send request with callback priorities', function () {
        const callbackSuccess1 = jasmine.createSpy('success1');
        const callbackSuccess2 = jasmine.createSpy('success2');

        ajax.sendRequest({
            url: '/goodRequest',
            onSuccess: [
                function (args) {
                    callbackSuccess1();
                },
                {
                    priority: 99,
                    callback: function (args) {
                        callbackSuccess2();
                    }
                }
            ]
        });

        expect(callbackSuccess1).toHaveBeenCalled();
        expect(callbackSuccess2).toHaveBeenCalledBefore(callbackSuccess1);
    });

    it('Send request without URL', function () {
        spyOn(window.console, 'error');
        ajax.sendRequest({});
        expect(window.console.error).toHaveBeenCalledWith('Value required: url');
    });

    it('Send request and update DOM with default mode', function () {
        $('body').append('<div id="ajax-result" class="html-test"><div class="content"></div></div>');
        const callbackSuccess = jasmine.createSpy('success');

        ajax.sendRequest({
            url: '/goodRequest',
            onSuccess: {
                callback: function (args) {
                    callbackSuccess($('#ajax-result').html());
                },
                priority: -99
            },
            update: '#ajax-result .content'
        });

        expect(callbackSuccess).toHaveBeenCalledWith('<div class="content">OK</div>');
    });

    it('Send request and update DOM with "update" mode', function () {
        testUpdate('update', '<div class="content">OK</div>');
    });

    it('Send request and update DOM with "before" mode', function () {
        testUpdate('before', 'OK<div class="content">X</div>');
    });

    it('Send request and update DOM with "after" mode', function () {
        testUpdate('after', '<div class="content">X</div>OK');
    });

    it('Send request and update DOM with "prepend" mode', function () {
        testUpdate('prepend', '<div class="content">OKX</div>');
    });

    it('Send request and update DOM with "append" mode', function () {
        testUpdate('append', '<div class="content">XOK</div>');
    });

    it('Send request and update DOM with bad mode', function () {
        spyOn(window.console, 'error');
        testUpdate('badMode', '<div class="content">X</div>');
        expect(window.console.error).toHaveBeenCalledWith('Bad updateMode: badMode');
    });

    it('Send request with method option', function () {
        ajax.sendRequest({
            url: '/goodRequest',
            method: 'GET'
        });

        expect(jasmine.Ajax.requests.mostRecent().method).toEqual('GET');
    });

    it('Send request with onBeforeSend option', function () {
        const callbackSuccess = jasmine.createSpy('success');
        const callbackBeforeSend = jasmine.createSpy('beforeSend');

        ajax.sendRequest({
            url: '/goodRequest',
            onBeforeSend: function (args) {
                callbackBeforeSend();
            },
            onSuccess: function (args) {
                callbackSuccess();
            }
        });

        expect(callbackSuccess).toHaveBeenCalled();
        expect(callbackBeforeSend).toHaveBeenCalledBefore(callbackSuccess);
    });

    it('Send request canceled by onBeforeSend option', function () {
        const callbackSuccess = jasmine.createSpy('success');
        const callbackBeforeSend = jasmine.createSpy('beforeSend');

        ajax.sendRequest({
            url: '/goodRequest',
            onBeforeSend: function (args) {
                callbackBeforeSend();
                args.stop = true;
            },
            onSuccess: function (args) {
                callbackSuccess();
            }
        });

        expect(callbackSuccess).not.toHaveBeenCalled();
        expect(callbackBeforeSend).toHaveBeenCalled();
    });

    it('Send request with data option', function () {
        ajax.sendRequest({
            url: '/goodRequest',
            data: {
                var1: 'value1',
                var2: 'value2'
            }
        });

        expect(jasmine.Ajax.requests.mostRecent().data()).toEqual({
            var1: ['value1'], var2: ['value2']
        });
    });

    it('Send request with JS in response', function () {
        $('body').append('<div id="ajax-result" class="html-test"><div class="content">X</div></div>');
        const callbackSuccess = jasmine.createSpy('success');

        ajax.sendRequest({
            url: '/resultJS',
            onSuccess: {
                callback: function (args) {
                    callbackSuccess($('#ajax-result').html());
                },
                priority: -99
            },
            update: '#ajax-result .content'
        });

        expect(callbackSuccess).toHaveBeenCalledWith('<div class="content"><div id="subcontent">AFTER</div><script>document.getElementById("subcontent").innerHTML="AFTER"</script></div>');
    });

    function testUpdate (updateMode, expectedContent) {
        $('body').append('<div id="ajax-result" class="html-test"><div class="content">X</div></div>');
        const callbackSuccess = jasmine.createSpy('success');

        ajax.sendRequest({
            url: '/goodRequest',
            onSuccess: {
                callback: function (args) {
                    callbackSuccess($('#ajax-result').html());
                },
                priority: -99
            },
            update: '#ajax-result .content',
            updateMode: updateMode
        });

        expect(callbackSuccess).toHaveBeenCalledWith(expectedContent);
    }
});

describe('Test Ajax.click', function () {
    beforeEach(function () {
        jasmine.Ajax.install();

        jasmine.Ajax.stubRequest('/goodRequest').andReturn({
            status: 200,
            responseText: 'OK'
        });
    });

    afterEach(function () {
        jasmine.Ajax.uninstall();
        $('.html-test').remove();
    });

    it('Send request with button', function () {
        $('body').append('<button class="html-test" id="buttonToTest">Go !</button>');

        const callbackSuccess = jasmine.createSpy('success');

        ajax.click($('#buttonToTest'), {
            url: '/goodRequest',
            onSuccess: function (args) {
                callbackSuccess();
            }
        });

        expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest');
        expect(callbackSuccess).toHaveBeenCalled();
    });

    it('Send request with button and data-*', function () {
        $('body').append('<button id="buttonToTest" class="html-test" data-ec-crud-ajax-url="/goodRequest" data-ec-crud-ajax-on-success="callbackSuccess()">Go !</button>');

        global.callbackSuccess = jasmine.createSpy('success');

        ajax.click($('#buttonToTest'));

        expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest');
        expect(global.callbackSuccess).toHaveBeenCalled();
    });

    it('Send request with button and data-* and options', function () {
        $('body').append('<button id="buttonToTest" class="html-test" data-ec-crud-ajax-on-success="callbackSuccess1()" data-ec-crud-ajax-method="PUT" data-ec-crud-ajax-url="/goodRequest">Go !</a>');

        global.callbackSuccess1 = jasmine.createSpy('success1');
        const callbackSuccess2 = jasmine.createSpy('success2');
        const callbackComplete = jasmine.createSpy('complete');

        ajax.click($('#buttonToTest'), {
            url: '/badRequest', // overridden by data-ec-crud-ajax-url
            method: 'GET', // overridden by data-ec-crud-ajax-method
            onSuccess: function (args) { // overridden by data-ec-crud-ajax-on-success
                callbackSuccess2();
            },
            onComplete: function (args) {
                callbackComplete();
            }
        });

        expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest');
        expect(jasmine.Ajax.requests.mostRecent().method).toBe('PUT');
        expect(global.callbackSuccess1).toHaveBeenCalled();
        expect(callbackSuccess2).not.toHaveBeenCalled();
        expect(callbackComplete).toHaveBeenCalled();
    });

    it('Send auto-request with button', function () {
        $('body').append('<button class="html-test ec-crud-ajax-click-auto" id="buttonToTest" data-ec-crud-ajax-url="/goodRequest">Go !</a>');

        $('#buttonToTest').click();

        expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest');
    });

    it('Send auto-request with button canceled', function () {
        $(document).on('ec-crud-ajax-click-auto-before', '#clickToTest', function (event) {
            event.preventDefault();
        });
        $('body').append('<button class="html-test ec-crud-ajax-click-auto" id="clickToTest" data-ec-crud-ajax-url="/goodRequest">Go !</button>');

        $('#clickToTest').click();

        expect(jasmine.Ajax.requests.mostRecent()).toBeUndefined();

        $(document).off('ec-crud-ajax-click-auto-before', '#clickToTest');
    });

    it('Send auto-request canceled by onBeforeSend option (function)', function () {
        $('body').append('<button class="html-test ec-crud-ajax-click-auto" id="clickToTest" data-ec-crud-ajax-url="/goodRequest" data-ec-crud-ajax-on-before-send="function (args) { args.stop = true; }">Go !</button>');

        $('#clickToTest').click();

        expect(jasmine.Ajax.requests.mostRecent()).toBeUndefined();

        $(document).off('ec-crud-ajax-click-auto-before', '#clickToTest');
    });

    it('Send auto-request canceled by onBeforeSend option (function content)', function () {
        $('body').append('<button class="html-test ec-crud-ajax-click-auto" id="clickToTest" data-ec-crud-ajax-url="/goodRequest" data-ec-crud-ajax-on-before-send="/args/args.stop = true">Go !</button>');

        $('#clickToTest').click();

        expect(jasmine.Ajax.requests.mostRecent()).toBeUndefined();

        $(document).off('ec-crud-ajax-click-auto-before', '#clickToTest');
    });
});

describe('Test Ajax.link', function () {
    beforeEach(function () {
        jasmine.Ajax.install();

        jasmine.Ajax.stubRequest('/goodRequest').andReturn({
            status: 200,
            responseText: 'OK'
        });
    });

    afterEach(function () {
        jasmine.Ajax.uninstall();
        $('.html-test').remove();
    });

    it('Send request with link', function () {
        $('body').append('<a href="/goodRequest" class="html-test" id="linkToTest">Go !</a>');

        const callbackSuccess = jasmine.createSpy('success');

        ajax.link($('#linkToTest'), {
            onSuccess: function (args) {
                callbackSuccess();
            }
        });

        expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest');
        expect(callbackSuccess).toHaveBeenCalled();
    });

    it('Send request with link and data-*', function () {
        $('body').append('<a href="/goodRequest" id="linkToTest" class="html-test" data-ec-crud-ajax-on-success="callbackSuccess()">Go !</a>');

        global.callbackSuccess = jasmine.createSpy('success');

        ajax.link($('#linkToTest'));

        expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest');
        expect(global.callbackSuccess).toHaveBeenCalled();
    });

    it('Send request with link and data-* and options', function () {
        $('body').append('<a href="/badRequest" id="linkToTest" class="html-test" data-ec-crud-ajax-on-success="callbackSuccess1()" data-ec-crud-ajax-method="PUT" data-ec-crud-ajax-url="/goodRequest">Go !</a>');
        // href is overridden by url option

        global.callbackSuccess1 = jasmine.createSpy('success1');
        const callbackSuccess2 = jasmine.createSpy('success2');
        const callbackComplete = jasmine.createSpy('complete');

        ajax.link($('#linkToTest'), {
            url: '/badRequest', // overridden by data-ec-crud-ajax-url
            method: 'GET', // overridden by data-ec-crud-ajax-method
            onSuccess: function (args) { // overridden by data-ec-crud-ajax-on-success
                callbackSuccess2();
            },
            onComplete: function (args) {
                callbackComplete();
            }
        });

        expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest');
        expect(jasmine.Ajax.requests.mostRecent().method).toBe('PUT');
        expect(global.callbackSuccess1).toHaveBeenCalled();
        expect(callbackSuccess2).not.toHaveBeenCalled();
        expect(callbackComplete).toHaveBeenCalled();
    });

    it('Send auto-request with link', function () {
        $('body').append('<a href="/goodRequest" class="html-test ec-crud-ajax-link-auto" id="linkToTest">Go !</a>');

        $('#linkToTest').click();

        expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest');
    });

    it('Send auto-request with link canceled', function () {
        $(document).on('ec-crud-ajax-link-auto-before', '#linkToTest', function (event) {
            event.preventDefault();
        });
        $('body').append('<a href="/goodRequest" class="html-test ec-crud-ajax-link-auto" id="linkToTest">Go !</a>');

        $('#linkToTest').click();

        expect(jasmine.Ajax.requests.mostRecent()).toBeUndefined();

        $(document).off('ec-crud-ajax-link-auto-before', '#linkToTest');
    });
});

describe('Test Ajax.form', function () {
    beforeEach(function () {
        jasmine.Ajax.install();

        jasmine.Ajax.stubRequest('/goodRequest').andReturn({
            status: 200,
            responseText: 'OK'
        });
    });

    afterEach(function () {
        jasmine.Ajax.uninstall();
        $('.html-test').remove();
    });

    it('Send request with form', function () {
        $('body').append('<form action="/goodRequest" method="POST" class="html-test" id="formToTest"><input type="text" name="var1" /><input type="text" name="var2" /></form>');
        $('#formToTest input[name=var1]').val('My value 1');
        $('#formToTest input[name=var2]').val('My value 2');

        const callbackSuccess = jasmine.createSpy('success');

        ajax.sendForm($('#formToTest'), {
            onSuccess: function (args) {
                callbackSuccess();
            }
        });

        expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest');
        expect(jasmine.Ajax.requests.mostRecent().method).toBe('POST');
        expect(jasmine.Ajax.requests.mostRecent().data()).toEqual({
            var1: ['My value 1'], var2: ['My value 2']
        });
        expect(callbackSuccess).toHaveBeenCalled();
    });

    it('Send request with PUT form', function () {
        $('body').append('<form action="/goodRequest" method="PUT" class="html-test" id="formToTest"><input type="text" name="var1" /><input type="text" name="var2" /></form>');
        $('#formToTest input[name=var1]').val('My value 1');
        $('#formToTest input[name=var2]').val('My value 2');

        const callbackSuccess = jasmine.createSpy('success');

        ajax.sendForm($('#formToTest'), {
            onSuccess: function (args) {
                callbackSuccess();
            }
        });

        expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest');
        expect(jasmine.Ajax.requests.mostRecent().method).toBe('PUT');
        expect(jasmine.Ajax.requests.mostRecent().data()).toEqual({
            var1: ['My value 1'], var2: ['My value 2']
        });
        expect(callbackSuccess).toHaveBeenCalled();
    });

    it('Send request with form and data-*', function () {
        $('body').append('<form action="/goodRequest" method="POST" class="html-test" id="formToTest" data-ec-crud-ajax-on-success="callbackSuccess()"><input type="text" name="var1" /><input type="text" name="var2" /></form>');
        $('#formToTest input[name=var1]').val('My value 1');
        $('#formToTest input[name=var2]').val('My value 2');

        global.callbackSuccess = jasmine.createSpy('success');

        ajax.sendForm($('#formToTest'));

        expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest');
        expect(jasmine.Ajax.requests.mostRecent().method).toBe('POST');
        expect(jasmine.Ajax.requests.mostRecent().data()).toEqual({
            var1: ['My value 1'], var2: ['My value 2']
        });
        expect(global.callbackSuccess).toHaveBeenCalled();
    });

    it('Send request with form and data-* and options', function () {
        $('body').append('<form action="/badRequest" method="POST" class="html-test" id="formToTest" data-ec-crud-ajax-on-success="callbackSuccess1()" data-ec-crud-ajax-method="PUT" data-ec-crud-ajax-url="/goodRequest"><input type="text" name="var1" /><input type="text" name="var2" /></form>');
        // action is overridden by url option
        $('#formToTest input[name=var1]').val('My value 1');
        $('#formToTest input[name=var2]').val('My value 2');

        global.callbackSuccess1 = jasmine.createSpy('success1');
        const callbackSuccess2 = jasmine.createSpy('success2');
        const callbackComplete = jasmine.createSpy('complete');

        ajax.sendForm($('#formToTest'), {
            url: '/badRequest', // overridden by data-ec-crud-ajax-url
            method: 'GET', // overridden by data-ec-crud-ajax-method
            onSuccess: function (args) { // overridden by data-ec-crud-ajax-on-success
                callbackSuccess2();
            },
            onComplete: function (args) {
                callbackComplete();
            }
        });

        expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest');
        expect(jasmine.Ajax.requests.mostRecent().method).toBe('PUT');
        expect(jasmine.Ajax.requests.mostRecent().data()).toEqual({
            var1: ['My value 1'],
            var2: ['My value 2']
        });
        expect(global.callbackSuccess1).toHaveBeenCalled();
        expect(callbackSuccess2).not.toHaveBeenCalled();
        expect(callbackComplete).toHaveBeenCalled();
    });

    it('Send auto-request with form', function () {
        $('body').append('<form action="/goodRequest" method="POST" class="html-test ec-crud-ajax-form-auto" id="formToTest"><input type="text" name="var1" /><input type="text" name="var2" /></form>');
        $('#formToTest input[name=var1]').val('My value 1');
        $('#formToTest input[name=var2]').val('My value 2');

        $('#formToTest').submit();

        expect(jasmine.Ajax.requests.mostRecent().url).toBe('/goodRequest');
        expect(jasmine.Ajax.requests.mostRecent().method).toBe('POST');
        expect(jasmine.Ajax.requests.mostRecent().data()).toEqual({
            var1: ['My value 1'],
            var2: ['My value 2']
        });
    });

    it('Send auto-request with form canceled', function () {
        $(document).on('ec-crud-ajax-form-auto-before', '#formToTest', function (event) {
            event.preventDefault();
        });
        $('body').append('<form action="/goodRequest" method="POST" class="html-test ec-crud-ajax-form-auto" id="formToTest"><input type="text" name="var1" /><input type="text" name="var2" /></form>');
        $('#formToTest input[name=var1]').val('My value 1');
        $('#formToTest input[name=var2]').val('My value 2');

        $('#formToTest').submit();

        expect(jasmine.Ajax.requests.mostRecent()).toBeUndefined();

        $(document).off('ec-crud-ajax-form-auto-before', '#formToTest');
    });
});
