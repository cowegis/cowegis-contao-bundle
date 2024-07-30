<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Control\Layers;

use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Map\Control\ControlTypeHydrator;
use Cowegis\Bundle\Contao\Model\ControlModel;
use Cowegis\Core\Definition\Control\LayersControl;
use Cowegis\Core\Definition\DefinitionId\IntegerDefinitionId;
use Cowegis\Core\Definition\Expression\InlineExpression;
use Cowegis\Core\Definition\Layer\LayerId;
use Cowegis\Core\Provider\Context;
use Doctrine\DBAL\Connection;

use function assert;

final class LayersControlHydrator extends ControlTypeHydrator
{
    /** @var list<string>|array<string,string> */
    protected static array $options = [
        'position',
        'collapsed',
        'autoZIndex',
        'hideSingleBase',
        'sortLayers',
    ];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function hydrate(object $data, object $definition, Context $context, Hydrator $hydrator): void
    {
        assert($data instanceof ControlModel);
        assert($definition instanceof LayersControl);

        parent::hydrate($data, $definition, $context, $hydrator);

        $this->hydrateLayers($data, $definition);
        $this->hydrateSortingFunction($data, $definition, $context);
        $this->hydrateNameFunction($data, $definition, $context);
    }

    protected function supportedType(): string
    {
        return 'layers';
    }

    private function hydrateLayers(ControlModel $controlModel, LayersControl $control): void
    {
        $result = $this->connection->executeQuery(
            'SELECT * FROM tl_cowegis_control_layer WHERE cid=:controlId ORDER BY sorting',
            ['controlId' => $controlModel->id()],
        );

        while ($row = $result->fetchAssociative()) {
            $layerId = LayerId::fromValue(IntegerDefinitionId::fromValue((int) $row['lid']));

            if ($row['mode'] === 'overlay') {
                $control->overlays()->addLayer($layerId);
            } else {
                $control->baseLayers()->addLayer($layerId);
            }
        }
    }

    private function hydrateSortingFunction(ControlModel $controlModel, LayersControl $control, Context $context): void
    {
        if (! (bool) $controlModel->sortLayers || $controlModel->sortFunction === null) {
            return;
        }

        $control->options()->set(
            'sortFunction',
            $context->callbacks()->add(new InlineExpression($controlModel->sortFunction)),
        );
    }

    private function hydrateNameFunction(ControlModel $controlModel, LayersControl $control, Context $context): void
    {
        if ($controlModel->nameFunction === null) {
            return;
        }

        $control->options()->set(
            'nameFunction',
            $context->callbacks()->add(new InlineExpression($controlModel->nameFunction)),
        );
    }
}
