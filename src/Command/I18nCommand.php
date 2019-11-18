<?php
declare(strict_types=1);

namespace ADmad\I18n\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * Command for interactive I18N management.
 */
class I18nCommand extends Command
{
    /**
     * Execute interactive mode
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $io->out('<info>I18n Command</info>');
        $io->hr();
        $io->out('[E]xtract translation messages from sources');
        $io->out('[I]nitialize a language');
        $io->out('[H]elp');
        $io->out('[Q]uit');

        $choice = null;
        while ($choice !== 'q') {
            $choice = strtolower($io->askChoice('What would you like to do?', ['E', 'I', 'H', 'Q']));
            $code = null;
            switch ($choice) {
                case 'e':
                    $code = $this->executeCommand(I18nExtractCommand::class, [], $io);
                    break;
                case 'i':
                    $code = $this->executeCommand(I18nInitCommand::class, [], $io);
                    break;
                case 'h':
                    $io->out($this->getOptionParser()->help());
                    break;
                default:
                    $io->err(
                        'You have made an invalid selection. ' .
                        'Please choose a command to execute by entering E, I, H, or Q.'
                    );
            }
            if ($code === static::CODE_ERROR) {
                $this->abort();
            }
        }

        return static::CODE_SUCCESS;
    }

    /**
     * Gets the option parser instance and configures it.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to update
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription(
            'I18n commands let you populate messages repository to power translations in your application.'
        );

        return $parser;
    }
}
