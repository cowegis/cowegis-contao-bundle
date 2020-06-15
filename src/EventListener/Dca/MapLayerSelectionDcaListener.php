<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Dca;

use Contao\Backend;
use Contao\BackendTemplate;
use Contao\DataContainer;
use Contao\Input;
use Contao\StringUtil;
use Cowegis\Bundle\Contao\Model\Map\MapModel;
use Cowegis\Bundle\Contao\Model\Map\MapRepository;
use Doctrine\DBAL\Connection;
use Netzmacht\Contao\Toolkit\Dca\Listener\AbstractListener;
use Netzmacht\Contao\Toolkit\Dca\Manager;
use PDO;
use RequestToken;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MapLayerSelectionDcaListener extends AbstractListener
{
    protected static $name = 'tl_cowegis_layer';

    /** @var Connection */
    private $connection;

    /** @var RouterInterface */
    private $router;

    /** @var TranslatorInterface */
    private $translator;
    /**
     * @var MapRepository
     */
    private $mapRepository;

    public function __construct(
        Manager $dcaManager,
        MapRepository $mapRepository,
        Connection $connection,
        RouterInterface $router,
        TranslatorInterface $translator
    ) {
        parent::__construct($dcaManager);

        $this->connection    = $connection;
        $this->router        = $router;
        $this->translator    = $translator;
        $this->mapRepository = $mapRepository;
    }

    public function initializeMapView(DataContainer $dataContainer) : void
    {
        if (Input::get('do') !== 'cowegis_map') {
            return;
        }

        $mapModel = $this->mapRepository->find((int) Input::get('id'));
        if (! $mapModel instanceof MapModel) {
            throw new BadRequestHttpException();
        }

        $definition = $this->getDefinition();
        $definition->modify(
            ['config'],
            static function (array $config) {
                $config['closed']       = true;
                $config['notCopyable']  = true;
                $config['notCreatable'] = true;
                $config['notDeletable'] = true;
                $config['notEditable']  = true;
                $config['notSortable']  = true;

                return $config;
            }
        );

        $definition->set(
            ['list', 'global_operations'],
            [
                'back'        => [
                    'label' => [$mapModel->title, $GLOBALS['TL_LANG']['MSC']['backBT'][1]],
                    'href'  => 'table=tl_cowegis_map&id=',
                    'class' => 'header header_back',
                ],
                'toggleNodes' => $definition->get(['list', 'global_operations', 'toggleNodes']),
            ]
        );
        $definition->set(
            ['list', 'operations'],
            [
                'map' => [
                    'button_callback' => [self::class, 'mapIntegrationButtons'],
                ],
            ]
        );
    }

    /**
     * @param array $row urrent row.
     *
     * @return string
     */
    public function mapIntegrationButtons(array $row) : string
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM tl_cowegis_map_layer WHERE pid=:mapId AND layerId=:layerId LIMIT 0,1'
        );
        $statement->bindValue('mapId', Input::get('id'));
        $statement->bindValue('layerId', $row['id']);
        $statement->execute();

        $layer    = $statement->fetch(PDO::FETCH_ASSOC);
        $template = new BackendTemplate('be_cowegis_map_layer_actions');
        $template->setData(
            [
                'exists'                => $statement->rowCount() > 0,
                'active'                => $layer['active'] ?? false,
                'action'                => $this->router->generate(
                    'cowegis_contao_backend_map_layer_actions',
                    [
                        'mapId'   => Input::get('id'),
                        'layerId' => $row['id'],
                    ]
                ),
                'editUrl'               => $statement->rowCount()
                    ? Backend::addToUrl('table=tl_cowegis_map_layer&amp;act=edit&amp;id=' . $layer['id'])
                    : null,
                'editLabel'             => $this->translate('tl_cowegis_map_layer.edit.0'),
                'editTitle'             => $this->translate('tl_cowegis_map_layer.edit.1'),
                'initialVisible'        => (bool) ($layer['initialVisible'] ?? false),
                'activateLabel'         => $this->translate('tl_cowegis_map_layer.activate.0'),
                'activateTitle'         => $this->translate('tl_cowegis_map_layer.activate.1'),
                'disableLabel'          => $this->translate('tl_cowegis_map_layer.disable.0'),
                'disableTitle'          => $this->translate('tl_cowegis_map_layer.disable.1'),
                'toggleVisibilityLabel' => $this->translate('tl_cowegis_map_layer.toggleVisibility.0'),
                'toggleVisibilityTitle' => $this->translate('tl_cowegis_map_layer.toggleVisibility.1'),

                'requestToken' => RequestToken::get(),
            ]
        );

        return $template->parse();
    }

    private function translate(string $key, array $params = [], ?string $domain = null) : string
    {
        $domain = $domain ?: 'contao_tl_cowegis_map_layer';

        return StringUtil::specialchars($this->translator->trans($key, $params, $domain));
    }
}
