<?php

namespace StorageTool\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use SplFileInfo;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Scan extends Command {


	/**
	 *
	 * @var Input\InputInterface
	 */
	private $input = null;

	/**
	 *
	 * @var OutputInterface
	 */
	private $output = null;

	/**
	 * @var SplfileInfo
	 */
	private $srcPath = '';

	/**
	 * @var array
	 */
	private $buffer = [];

	/**
	 * @var int
	 */
	private $bufferThreshold = 100;

	protected function configure() {
		$this
			->setName( 'scan' )
			->setDescription( 'Scans a storage' )
			->setDefinition( new Input\InputDefinition( [
				new Input\InputOption(
					'id',
					null,
					Input\InputOption::VALUE_REQUIRED,
					'The storage identified'
				),
				new Input\InputOption(
					'src',
					null,
					Input\InputOption::VALUE_REQUIRED,
					'Path to scan'
				)
			] ) );

		return parent::configure();
	}

	protected function execute( Input\InputInterface $input, OutputInterface $output ) {
		$this->input = $input;
		$this->output = $output;

		$this->ensureDatabase();
		$this->initSrcPath();
		$this->scanSrc();
		$this->outputReport();
	}

	private function ensureDatabase() {

	}

	private function initSrcPath() {
		$srcPath = $this->input->getOption( 'src' );
		$this->srcPath = new SplFileInfo( $srcPath );

		if( !file_exists( $this->srcPath->getPathname() ) ) {
			$this->output->writeln( "<error>Source path '$srcPath' does not exist!</error>" );
		} 
	}

	private function scanSrc() {
		$directory = new RecursiveDirectoryIterator( $this->srcPath );
		$iterator = new RecursiveIteratorIterator($directory);
		
		foreach ( $iterator as $info ) {
			if( $this->isDot( $info )  ) {
				continue;
			}
			if( $info->isDir()  ) {
				continue;
			}
			if( $info->isLink()  ) {
				continue;
			}
			$this->output->writeln( $info->getPathname() );
			$this->addToBuffer( $info );
			if( $this->bufferIsFull() ) {
				$this->writeBufferToDatabase();
			}
		}
		$this->writeBufferToDatabase();
	}

	/**
	 * @param SplFileInfo $info
	 */
	private function addToBuffer( $info ) {
		$this->buffer[] = [
			'pathname' => $info->getPathname(),
			'extension' => $info->getExtension(),
			'type' => $info->getType(),
			'size' => $info->getSize(),
			'atime' => $info->getATime(),
			'mtime' => $info->getMTime(),
			'ctime' => $info->getCTime(),
			'sha1' => sha1_file( $info->getPathname() )
		];
	}

	/**
	 * @return bool
	 */
	private function bufferIsFull() {
		return count( $this->buffer ) >= $this->bufferThreshold;
	}

	private function writeBufferToDatabase() {
		var_dump( $this->buffer );

		$this->buffer = [];
	}

	/**
	 * @param SplInfo $info
	 * @return bool 
	 */
	private function isDot( $info ) {
		return $info->getFilename() === '.' || $info->getFilename() === '..';
	}

	private function outputReport() {
		$this->output->writeln( '<info>--> Done.</info>' );
	}
}
