<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Control\Layers;

use Cowegis\Bundle\Contao\Hydrator\Control\ControlTypeHydrator;
use Cowegis\Bundle\Contao\Model\ControlModel;
use Cowegis\Core\Definition\Control\LayersControl;
use Cowegis\Core\Definition\DefinitionId\IntegerDefinitionId;
use Cowegis\Core\Definition\Expression\InlineExpression;
use Cowegis\Core\Definition\Layer\LayerId;
use Cowegis\Core\Provider\Context;
use Doctrine\DBAL\Connection;
use PDO;
use function assert;

final class LayersControlHydrator extends ControlTypeHydrator
{
    protected const OPTIONS = [
        'position',
        'collapsed',
        'autoZIndex',
        'hideSingleBase',
        'sortLayers',
    ];

    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function hydrate(object $controlModel, object $control, Context $context) : void
    {
        assert($controlModel instanceof ControlModel);
        assert($control instanceof LayersControl);

        parent::hydrate($controlModel, $control, $context);

        $this->hydrateLayers($controlModel, $control);
        $this->hydrateSortingFunction($controlModel, $control, $context);
        $this->hydrateNameFunction($controlModel, $control, $context);
    }

    protected function supportedType() : string
    {
        return 'layers';
    }

    private function hydrateLayers(ControlModel $controlModel, LayersControl $control) : void
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM tl_cowegis_control_layer WHERE cid=:controlId ORDER BY sorting'
        );
        $statement->bindValue('controlId', $controlModel->id());
        $statement->execute();

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $layerId = LayerId::fromValue(IntegerDefinitionId::fromValue((int) $row['lid']));

            if ($row['mode'] === 'overlay') {
                $control->overlays()->addLayer($layerId);
            } else {
                $control->baseLayers()->addLayer($layerId);
            }
        }
    }

    private function hydrateSortingFunction(ControlModel $controlModel, LayersControl $control, Context $context) : void
    {
        if (! $controlModel->sortLayers || ! $controlModel->sortFunction) {
            return;
        }

        $control->options()->set(
            'sortFunction',
            $context->callbacks()->add(new InlineExpression($controlModel->sortFunction))
        );
    }

    private function hydrateNameFunction(ControlModel $controlModel, LayersControl $control, Context $context) : void
    {
        if (! $controlModel->nameFunction) {
            return;
        }

        $control->options()->set(
            'nameFunction',
            $context->callbacks()->add(new InlineExpression($controlModel->nameFunction))
        );
    }
}
