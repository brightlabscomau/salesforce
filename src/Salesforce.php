<?php

namespace brightlabs\craftsalesforce;

use Craft;
use brightlabs\craftsalesforce\elements\Assignment;
use brightlabs\craftsalesforce\models\Settings;
use brightlabs\craftsalesforce\services\Assignment as AssignmentService;
use brightlabs\craftsalesforce\variables\CraftVariableBehavior;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\DefineBehaviorsEvent;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterCpNavItemsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\fieldlayoutelements\TextField;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\services\Elements;
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
 */
class Salesforce extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;

    public static function config(): array
    {
        return [
            'components' => ['assignment' => AssignmentService::class],
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
        });
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
            // $event->rules['assignments'] = ['template' => 'salesforce/assignments/_index.twig'];
            // $event->rules['salesforce/assignments/<elementId:\\d+>'] = 'elements/edit';
            $event->rules['salesforce/assignments/<assignmentId:\\d+>'] = 'salesforce/assignments/edit';
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
                        'assignmentFields' => [
                            'label' => 'Assignment Fields',
                            'url' => 'salesforce/fields'
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
            FieldLayout::class,
            FieldLayout::EVENT_DEFINE_NATIVE_FIELDS,
            function (DefineFieldLayoutFieldsEvent $event) {
                // $fieldLayout = $event->sender;

                // $event->fields[] = [
                //     'class' => TextField::class,
                //     'label' => 'Title',
                //     'attribute' => 'title',
                //     'type' => 'text',
                //     'mandatory' => true,
                // ];
            }
        );
    }
}
