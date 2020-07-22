<?php

namespace born05\assetusage\services;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Asset as AssetElement;

class Asset extends Component
{
    private $usedAssetIds = null;
    /**
     * Determines if an asset is in use or not.
     *
     * @param  AssetElement $asset
     * @return string
     */

    public function getRedactorFields(): array
    {
        $results = (new Query())
            ->select(['handle'])
            ->from(Table::FIELDS)
            ->where(['type' => 'craft\redactor\Field'])
            ->andWhere(['context' => "global"])
            ->all();

        $handles = [];

        foreach ($results as $handle) {
            array_push($handles, "field_" . $handle["handle"]);
        }
        return $handles;
    }

    public function getMatrixRedactorFields()
    {
        $matrixHandels = (new Query())
            ->select(['handle', 'context'])
            ->from(Table::FIELDS)
            ->where(['type' => 'craft\redactor\Field'])
            ->andWhere("context != 'global'")
            ->all();

        $handles = [];

        foreach ($matrixHandels as $handle) {
            if ($handle["handle"] == "textBody") {
                array_push($handles, "field_text_" . $handle["handle"]);
            // } else if ($handle["handle"] == "table") {
            //     array_push($handles, "field_table_" . $handle["handle"]);
            }
        }

        return $handles;
    }

    public function getRedactorFromMatrix(array $handles)
    {
        $matrixResults = (new Query())
            ->select($handles)
            ->from('c54ft_matrixcontent_modularcontent')
            ->all();

        return $matrixResults;
    }

    public function getRedactorContent(array $handles): array
    {
        $results = (new Query())
            ->select($handles)
            ->from(Table::CONTENT)
            ->all();

        return $results;
    }

    public function getUsage(AssetElement $asset)
    {
        $results = (new Query())
            ->select(['id'])
            ->from(Table::RELATIONS)
            ->where(['targetId' => $asset->id])
            ->orWhere(['sourceId' => $asset->id])
            ->all();
        $count = count($results);


        if ($count > 0) {
            return Craft::t('assetusage', 'Used');
        } else if ($this->getUsageFromRedactor($asset->id) > 0) {
            return Craft::t('assetusage', 'Used');
        } else if ($this->getUsageFromRedactorMatrix($asset->id) > 0) {
            return Craft::t('assetusage', 'Used');
        }

        return '<span style="color: #da5a47;">' . Craft::t('assetusage', 'Unused') . '</span>';
    }

    public function getUsageFromRedactor(int $id)
    {
        $handles = $this->getRedactorFields();
        $redactorContent = $this->getRedactorContent($handles);
        $count = 0;

        foreach ($handles as $handel) {
            foreach ($redactorContent as $result) {
                if (strpos($result[$handel], "asset:$id:url")) {
                    $count++;
                }
            }
        }
        return $count;
    }
    public function getUsageFromRedactorMatrix(int $id)
    {
        $handles = $this->getMatrixRedactorFields();
        $redactorContent = $this->getRedactorFromMatrix($handles);
        $count = 0;

        foreach ($handles as $handel) {
            foreach ($redactorContent as $result) {
                if (strpos($result[$handel], "asset:$id:url")) {
                    $count++;
                }
            }
        }
        return $count;
    }
}
