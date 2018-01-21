<?php
/**
 * Created by PhpStorm.
 * User: fabrice
 * Date: 21/01/2018
 * Time: 15:43
 */

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetSecretCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('app:secret')
            ->setDescription('Get secret based on auth_id and auth_salt parameters')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $service = $this->getContainer()->get('app.service.auth');
        $output->writeln($service->retrieveSecret());
    }
}