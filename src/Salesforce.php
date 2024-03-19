<?php

namespace brightlabs\craftsalesforce;

use Craft;
use brightlabs\craftsalesforce\elements\Assignment;
use brightlabs\craftsalesforce\elements\Log;
use brightlabs\craftsalesforce\fields\AssignmentSectors;
use brightlabs\craftsalesforce\models\Settings;
use brightlabs\craftsalesforce\services\Assignment as AssignmentService;
use brightlabs\craftsalesforce\services\Log as LogService;
use brightlabs\craftsalesforce\variables\CraftVariableBehavior;
use craft\base\Model;
use craft\base\Plugin;
use craft\db\Table;
use craft\events\DefineBehaviorsEvent;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterCpNavItemsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\services\Elements;
use craft\services\Fields;
use craft\services\Gc;
use craft\web\UrlManager;
use craft\web\twig\variables\Cp;
use craft\web\twig\variables\CraftVariable;
use yii\base\Event;

/**
 * Salesforce plugin
 *
 * @method static Salesforce getInstance()
 * @method Settings getSettings()
 * @author Bright Labs <devs@brightlabs.com.au>
 * @copyright Bright Labs
 * @license MIT
 * @property-read AssignmentService $assignment
 * @property-read LogService $log
 */
class Salesforce extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;

    public static function config(): array
    {
        return [
            'components' => [
                'assignment' => AssignmentService::class,
                'log' => LogService::class
            ],
        ];
    }

    public function init(): void
    {

        parent::init();

        // Defer most setup tasks until Craft is fully initialized
        Craft::$app->onInit(function() {
            $this->attachEventHandlers();
            // ...
        });

    }

    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('salesforce/settings'));
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('salesforce/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    private function attachEventHandlers(): void
    {
        // Register event handlers here ...
        // (see https://craftcms.com/docs/4.x/extend/events.html to get started)
        Event::on(Elements::class, Elements::EVENT_REGISTER_ELEMENT_TYPES, function (RegisterComponentTypesEvent $event) {
            $event->types[] = Assignment::class;
            $event->types[] = Log::class;
        });
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
            $event->rules['salesforce/assignments/<assignmentId:\\d+>'] = 'salesforce/assignments/edit';
            $event->rules['salesforce/logs/<logId:\\d+>'] = 'salesforce/logs/edit';
        });

        Event::on(
            Cp::class,
            Cp::EVENT_REGISTER_CP_NAV_ITEMS,
            function(RegisterCpNavItemsEvent $event) {
                $event->navItems[] = [
                    'url' => 'salesforce',
                    'label' => 'Salesforce',
                    'subnav' => [
                        'assignments' => [
                            'label' => 'Assignments',
                            'url' => 'salesforce/assignments'
                        ],
                        'logs' => [
                            'label' => 'Logs',
                            'url' => 'salesforce/logs'
                        ],
                        'settings' => [
                            'label' => 'Settings',
                            'url' => 'salesforce/settings'
                        ]
                    ]
                ];
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['salesforce/assignments'] = 'salesforce/assignments';
                $event->rules['salesforce/assignments/save'] = 'salesforce/assignments/save';
                $event->rules['salesforce/settings'] = 'salesforce/settings';
                $event->rules['salesforce'] = 'salesforce/assignments';
                $event->rules['salesforce/fields'] = 'salesforce/settings/fields';
                $event->rules['salesforce/test'] = 'salesforce/test';

                $event->rules['salesforce/logs'] = 'salesforce/logs';

            }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_DEFINE_BEHAVIORS,
            function (DefineBehaviorsEvent $event) {
                $event->sender->attachBehaviors([
                    CraftVariableBehavior::class
                ]);
            }
        );

        Event::on(
            Gc::class,
            Gc::EVENT_RUN,
            function (Event $event) {
                // Delete `elements` table rows without peers in our custom assignments table
                Craft::$app->getGc()->deletePartialElements(
                    Assignment::class,
                    'salesforce_assignments',
                    'id'
                );

                // Delete `elements` table rows without corresponding `content` table rows for the custom element
                Craft::$app->getGc()->deletePartialElements(
                    Assignment::class,
                    Table::CONTENT,
                    'elementId',
                );

                // Delete `elements` table rows without peers in our custom logs table
                Craft::$app->getGc()->deletePartialElements(
                    Log::class,
                    'salesforce_logs',
                    'id'
                );

                // Delete `elements` table rows without corresponding `content` table rows for the custom element
                Craft::$app->getGc()->deletePartialElements(
                    Log::class,
                    Table::CONTENT,
                    'elementId',
                );
            }
        );
        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function (RegisterComponentTypesEvent $event) {
            $event->types[] = AssignmentSectors::class;
        });
    }
}
