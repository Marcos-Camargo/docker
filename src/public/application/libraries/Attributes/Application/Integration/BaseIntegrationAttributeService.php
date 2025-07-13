<?php

namespace libraries\Attributes\Application\Integration;

require_once APPPATH . "libraries/Helpers/StringHandler.php";

use libraries\Attributes\Application\Integration\Mappers\BaseIntegrationAttributeMapper;
use libraries\Attributes\Application\Resources\CustomAttribute;
use libraries\Helpers\StringHandler;

/**
 * Class BaseIntegrationAttributeService
 * @package libraries\Attributes\Application\Integration
 * @property \CI_DB_query_builder $db
 * @property BaseIntegrationAttributeMapper $integrationAttributeMapper
 */
class BaseIntegrationAttributeService
{
    protected $integrationAttrIgnored = [];

    protected $mappedIntegrationAttributes = [];

    public function __construct(BaseIntegrationAttributeMapper $integrationAttributeMapper)
    {
        $this->integrationAttributeMapper = $integrationAttributeMapper;
    }

    public function mapSearchCriteria(array $criteria = []): array
    {
        return $criteria;
    }

    public function fetchIntegrationAttributes(array $integration, array $addCriteria = [], bool $ignore_value_field = false): array
    {
        $offset = 0;
        $limit = 100;
        $integrationAttributes = [];
        while (true) {
            if ($ignore_value_field) {
                $this->db->select('id_integration,id_categoria,id_atributo,obrigatorio,integrado,int_to,variacao,nome,tipo,multi_valor,atributo_variacao,tooltip,prd_sku,name_md5');
            }
            $this->db->from('atributos_categorias_marketplaces')
                ->where('int_to', $integration['int_to']);
            $inIntegration = implode(',', [(int)$integration['id'], (int)($integration['main_integration_id'] ?? null)]);
            $attributes = $this->db->where($this->handleWithCriteria($addCriteria))
                ->where(["id_integration IN ({$inIntegration})" => null])
//                ->group_by('id_atributo')
                ->limit($limit, $offset)
                ->get()->result_array();
            if (empty($attributes)) break;
            $integrationAttributes = array_merge($integrationAttributes, $attributes);
            $offset += $limit;
        }
        return $integrationAttributes;
    }

    public function mapperIntegrationAttributes(array $integrationAttributes = [], array $data = [])
    {
        if (empty($integrationAttributes)) return [];

        $this->mappedIntegrationAttributes = [];
        foreach ($integrationAttributes as $attribute) {
            if ($this->checkIntegrationIgnoredAttr([
                'name' => $attribute['nome'],
                'code' => $attribute['id_atributo']
            ])) continue;

            $id_integration = $attribute["id_integration"];
            $id_categoria   = $attribute["id_categoria"];
            $id_atributo    = $attribute["id_atributo"];
            $int_to         = $attribute["int_to"];

            $value_attribute_marketplace = '[]';
            $attribute_marketplace = $this->getValueAttributeMarketplace($id_integration, $id_categoria, $id_atributo, $int_to);
            if ($attribute_marketplace) {
                $value_attribute_marketplace = $attribute_marketplace['valor'];
            }

            $index = StringHandler::slugify($attribute['nome'], '_') . (!empty($data['category_id'] ?? 0) ? "_{$data['category_id']}" : '');
            $values = json_decode(empty($value_attribute_marketplace) ? '[]' : $value_attribute_marketplace);
            if (!isset($this->mappedIntegrationAttributes[$index])) {
                $this->mappedIntegrationAttributes[$index] = [
                    'name' => $attribute['nome'],
                    'code' => $attribute['id_atributo'],
                    'module' => $data['module'] ?? null,
                    'category_id' => $data['category_id'] ?? 0,
                    'active' => $data['active'] ?? CustomAttribute::STATUS_ENABLED,
                    'required' => ($attribute['obrigatorio'] ?? 0) == 1 ? CustomAttribute::REQUIRED : CustomAttribute::NOT_REQUIRED,
                    'field_type' => $this->integrationAttributeMapper->mapIntegrationFieldType($attribute['tipo'] ?? ''),
                    'values' => []
                ];
            }
            if (!empty($values)) {
                $this->mappedIntegrationAttributes[$index]['values'] = array_merge(
                    $this->mappedIntegrationAttributes[$index]['values'],
                    $this->integrationAttributeMapper->mapperIntegrationAttributeValues($values, $index)
                );
            }
        }
        return $this->mappedIntegrationAttributes;
    }

    public function mergeAttributeValues(array $baseAttributes, array $integrationAttributes): array
    {
        $mergedAttributes = $baseAttributes;
        foreach ($mergedAttributes as $k => $mergedAttribute) {
            if ($this->checkIntegrationIgnoredAttr($mergedAttribute)) continue;
            $commonAttributes = array_filter($integrationAttributes, function ($attr) use ($mergedAttribute) {
                return (strcasecmp($attr['name'], $mergedAttribute['name']) === 0) || (strcasecmp($attr['code'], $mergedAttribute['code']) === 0);
            });
            foreach ($commonAttributes ?? [] as $commonAttribute) {
                foreach ($commonAttribute['values'] ?? [] as $value) {
                    $hasValue = array_filter($mergedAttributes[$k]['values'] ?? [], function ($v) use ($value) {
                        return $v['value'] == $value['value'];
                    });
                    if (!empty($hasValue)) continue;
                    if (!isset($mergedAttributes[$k]['values'])) $mergedAttributes[$k]['values'] = [];
                    array_push($mergedAttributes[$k]['values'], $value);
                }
            }
        }

        return $mergedAttributes;
    }

    protected function handleWithCriteria($criteria)
    {
        $whereIn = array_filter($criteria, function ($f) {
            return is_array($f);
        });
        foreach ($whereIn as $f => $v) {
            unset($criteria[$f]);
            if ($f == 'or' && is_array($v)) {
                $orInSQL = [];
                foreach ($v as $fIn => $vIn) {
                    $inChar = array_map(function ($i) {
                        return is_string($i) ? "'{$i}'" : "{$i}";
                    }, $vIn);
                    $inChar = implode(',', $inChar);
                    $orInSQL[] = "{$fIn} IN ({$inChar})";
                }
                if (!empty($orInSQL)) {
                    $implodeOr = implode(' OR ', $orInSQL);
                    $this->db->where("({$implodeOr})", null);
                }
                continue;
            }
            $this->db->where_in($f, $v);
        }
        return $criteria;
    }

    protected function checkIntegrationIgnoredAttr($attribute): bool
    {
        return in_array($attribute['name'], $this->integrationAttrIgnored) || in_array($attribute['code'], $this->integrationAttrIgnored);
    }

    public function buildCriteriaEnabledAttributes(array $attributes = []): array
    {
        $criteria = ['LOWER(nome)' => [], 'LOWER(id_atributo)' => []];
        foreach ($attributes as $attribute) {
            if (!$attribute['active']) continue;
            $attribute['name'] = strtolower($attribute['name']);
            $attribute['code'] = strtolower($attribute['code']);
            if ($this->checkIntegrationIgnoredAttr($attribute)) continue;

            array_push($criteria['LOWER(nome)'], $attribute['name'], $attribute['code']);
            array_push($criteria['LOWER(id_atributo)'], $attribute['name'], $attribute['code']);
        }
        if (!empty($criteria['LOWER(nome)'])) {
            return ['or' => $criteria];
        }
        return $criteria;
    }

    public function __get($name)
    {
        return get_instance()->{$name};
    }

    public function getValueAttributeMarketplace($id_integration, $id_categoria, $id_atributo, $int_to): ?array
    {
        return $this->db->get_where('atributos_categorias_marketplaces', array(
            'id_integration' => $id_integration,
            'id_categoria' => $id_categoria,
            'id_atributo' => $id_atributo,
            'int_to' => $int_to
        ))->row_array();
    }
}