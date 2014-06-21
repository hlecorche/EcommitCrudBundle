function scrollToFirstMessage(scrollToError, scrollToFlashMessage, openTableIfMessage)
{
    if( typeof(scrollToError) == 'undefined' ){
        scrollToError = true;
    }
    if( typeof(scrollToFlashMessage) == 'undefined' ){
        scrollToFlashMessage = true;
    }
    if( typeof(openTableIfMessage) == 'undefined' ){
        openTableIfMessage = true;
    }

    $(document).ready(function() {
        if (scrollToFlashMessage) {
            var cible = $('.flash-message:first');
            if(cible.length > 0) {
                if (openTableIfMessage) {
                    openTable(cible);
                }
                height = cible.offset().top;
                $('html,body').animate({scrollTop:height});
                return;
            }
        }

        if (scrollToError) {
            var cible = $('ul.form_error:first');
            if(cible.length > 0) {
                if (openTableIfMessage) {
                    openTable(cible);
                }
                height = cible.offset().top;
                $('html,body').animate({scrollTop:height});
                return;
            }
        }
    });
}

function openTable(cible)
{
    pane = cible.parents('div.pane-auto-display-first-message');
    table = cible.parents('div.tab-auto-display-first-message');
    if(pane.length > 0 && table.length > 0) {
        idTable = table.attr('id');
        indexPane = pane.prevAll("#"+idTable+" div.pane-auto-display-first-message").length;
        api = $("#"+idTable).tabs();
        api.click(indexPane);
    }
}