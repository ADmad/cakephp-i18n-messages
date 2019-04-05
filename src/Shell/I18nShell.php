<?php
declare(strict_types=1);
namespace ADmad\I18n\Shell;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Shell for I18N management.
 *
 * @property \ADmad\I18n\Shell\Task\ExtractTask $Extract
 */
class I18nShell extends Shell
{
    use LocatorAwareTrait;

    /**
     * Contains tasks to load and instantiate.
     *
     * @var array
     */
    public $tasks = ['ADmad/I18n.Extract'];

    /**
     * Override main() for help message hook.
     *
     * @return bool|int|null
     */
    public function main()
    {
        $this->out('<info>I18n Shell</info>');
        $this->hr();
        $this->out('[E]xtract messages from sources');
        $this->out('[I]nitialize a language');
        $this->out('[H]elp');
        $this->out('[Q]uit');

        $choice = strtolower($this->in('What would you like to do?', ['E', 'I', 'H', 'Q']));
        switch ($choice) {
            case 'e':
                $this->Extract->main();
                break;
            case 'i':
                $this->init();
                break;
            case 'h':
                $this->out($this->OptionParser->help());
                break;
            case 'q':
                $this->_stop();

                return null;
            default:
                $this->out(
                    'You have made an invalid selection. Please choose a command to execute by entering E, I, H, or Q.'
                );
        }
        $this->hr();
        $this->main();

        return null;
    }

    /**
     * Initialize a new languages from exiting messages.
     *
     * Any existing translations for specified language will be deleted.
     *
     * @param string|null $language Language code to use.
     *
     * @return int|null
     */
    public function init($language = null): ?int
    {
        if ($language === null) {
            $language = $this->in('Please specify language code, e.g. `en`, `eng`, `en_US` etc.');
        }
        if (strlen($language) < 2) {
            return (int)$this->err('Invalid language code. Valid is `en`, `eng`, `en_US` etc.');
        }

        /** @var string $model */
        $model = $this->param('model');
        if (empty($model)) {
            $model = 'I18nMessages';
        }

        $fields = ['domain', 'singular', 'plural', 'context'];

        $model = $this->getTableLocator()->get($model);
        $messages = $model->find()
            ->select($fields)
            ->distinct(['domain', 'singular', 'context'])
            ->enableHydration(false)
            ->toArray();

        $entities = $model->newEntities($messages);

        $return = $model->getConnection()->transactional(
            function () use ($model, $entities, $language): ?bool {
                $model->deleteAll([
                    'locale' => $language,
                ]);

                foreach ($entities as $entity) {
                    $entity->set('locale', $language);
                    if ($model->save($entity) === false) {
                        return false;
                    }
                }

                return null;
            }
        );

        if ($return) {
            $this->out('Created ' . count($messages) . ' messages for "' . $language . '"');
        } else {
            $this->out('Unable to create messages for "' . $language . '"');
        }

        return null;
    }

    /**
     * Gets the option parser instance and configures it.
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser(): ConsoleOptionParser
    {
        $parser = parent::getOptionParser();
        $initParser = [
            'options' => [
                'model' => [
                    'help' => 'Model name.',
                    'short' => 'm',
                ],
            ],
            'arguments' => [
                'language' => [
                    'help' => 'Language code, e.g. `en`, `eng`, `en_US`.',
                ],
            ],
        ];

        $parser->setDescription(
            'I18n Shell extracts translation messages from source code and adds to database.'
        )->addSubcommand('extract', [
            'help' => 'Extract translation messages from your application',
            'parser' => $this->Extract->getOptionParser(),
        ])
        ->addSubcommand('init', [
            'help' => 'Initialize new language',
            'parser' => $initParser,
        ]);

        return $parser;
    }
}
