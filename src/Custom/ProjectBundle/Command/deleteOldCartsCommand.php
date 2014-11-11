<?php

namespace Custom\ProjectBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Drufony\CoreBundle\Model\CommerceUtils;


class deleteOldCartsCommand extends ContainerAwareCommand
{


    protected function configure()
    {
        $this
            ->setName('custom:deleteCarts')
	    ->setDescription('Delete not finished carts');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
	CommerceUtils::deleteOldCarts();

    }


}
