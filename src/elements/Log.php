<?php

namespace brightlabs\craftsalesforce\elements;

use Craft;
use brightlabs\craftsalesforce\elements\conditions\LogCondition;
use brightlabs\craftsalesforce\elements\db\LogQuery;
use craft\base\Element;
use craft\elements\User;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\UrlHelper;
use craft\web\CpScreenResponseBehavior;
use yii\web\Response;
use craft\helpers\Db;

/**
 * Log element type
 */
class Log extends Element
{
    public string $handle = 'log';
    public ?string $logDetails = null;
    public ?string $logErrors = null;

    public static function displayName(): string
    {
        return Craft::t('salesforce', 'Log');
    }

    public static function lowerDisplayName(): string
    {
        return Craft::t('salesforce', 'log');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('salesforce', 'Logs');
    }

    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('salesforce', 'logs');
    }

    public static function refHandle(): ?string
    {
        return 'log';
    }

    public static function trackChanges(): bool
    {
        return true;
    }

    public static function hasContent(): bool
    {
        return true;
    }

    public static function hasTitles(): bool
    {
        return true;
    }

    public static function hasUris(): bool
    {
        return true;
    }

    public static function isLocalized(): bool
    {
        return false;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function find(): ElementQueryInterface
    {
        return Craft::createObject(LogQuery::class, [static::class]);
    }

    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(LogCondition::class, [static::class]);
    }

    protected static function defineSources(string $context): array
    {
        return [
            [
                'key' => '*',
                'label' => Craft::t('salesforce', 'All logs'),
            ],
        ];
    }

    protected static function defineActions(string $source): array
    {
        // List any bulk element actions here
        return [];
    }

    protected static function includeSetStatusAction(): bool
    {
        return true;
    }

    protected static function defineSortOptions(): array
    {
        return [
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            // ...
        ];
    }

    protected static function defineTableAttributes(): array
    {
        return [
            // 'slug' => ['label' => Craft::t('app', 'Slug')],
            // 'uri' => ['label' => Craft::t('app', 'URI')],
            // 'link' => ['label' => Craft::t('app', 'Link'), 'icon' => 'world'],
            // 'id' => ['label' => Craft::t('app', 'ID')],
            // 'uid' => ['label' => Craft::t('app', 'UID')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            // 'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
            // ...
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'link',
            'dateCreated',
            // ...
        ];
    }

    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            // ...
        ]);
    }

    public function getUriFormat(): ?string
    {
        // If logs should have URLs, define their URI format here
        return null;
    }

    protected function previewTargets(): array
    {
        $previewTargets = [];
        $url = $this->getUrl();
        if ($url) {
            $previewTargets[] = [
                'label' => Craft::t('app', 'Primary {type} page', [
                    'type' => self::lowerDisplayName(),
                ]),
                'url' => $url,
            ];
        }
        return $previewTargets;
    }

    protected function route(): array|string|null
    {
        // Define how logs should be routed when their URLs are requested
        return [
            'templates/render',
            [
                'template' => 'site/template/path',
                'variables' => ['log' => $this],
            ]
        ];
    }

    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('viewLogs');
    }

    public function canSave(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('saveLogs');
    }

    public function canDuplicate(User $user): bool
    {
        if (parent::canDuplicate($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('saveLogs');
    }

    public function canDelete(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }
        // todo: implement user permissions
        return $user->can('deleteLogs');
    }

    public function canCreateDrafts(User $user): bool
    {
        return true;
    }

    protected function cpEditUrl(): ?string
    {
        return sprintf('logs/%s', $this->getCanonicalId());
    }

    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('logs');
    }

    public function prepareEditScreen(Response $response, string $containerId): void
    {
        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs([
            [
                'label' => self::pluralDisplayName(),
                'url' => UrlHelper::cpUrl('logs'),
            ],
        ]);
    }

    public function afterSave(bool $isNew): void
    {
        Db::upsert('{{%salesforce_logs}}', [
            'id' => $this->id,
        ], [
            'logDetails' => $this->logDetails,
            'logErrors' => $this->logErrors,
        ]);

        if (!$this->propagating) {
            Db::upsert('{{%salesforce_logs}}', [
                'id' => $this->id,
            ], [
                'logDetails' => $this->logDetails,
                'logErrors' => $this->logErrors,
            ]);
        }

        parent::afterSave($isNew);
    }
}
