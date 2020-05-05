<?php

require __DIR__.'/App/config/bootstrap.php';

function bootstrap(): void
{
    $kernel = new \Ecommit\CrudBundle\Tests\App\Kernel('test', true);
    $kernel->boot();

    $application = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
    $application->setAutoExit(false);

    $application->run(new \Symfony\Component\Console\Input\ArrayInput([
        'command' => 'doctrine:database:drop',
        '--force' => true,
    ]));

    $application->run(new \Symfony\Component\Console\Input\ArrayInput([
        'command' => 'doctrine:database:create',
    ]));

    $application->run(new \Symfony\Component\Console\Input\ArrayInput([
        'command' => 'doctrine:schema:update',
        '--force' => true,
    ]));

    $application->run(new \Symfony\Component\Console\Input\ArrayInput([
        'command' => 'doctrine:fixtures:load',
        '--no-interaction' => true,
    ]));
}

bootstrap();
