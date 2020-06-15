<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Dca;

use Contao\DataContainer;
use Contao\Input;
use Cowegis\Bundle\Contao\Model\Map\MapModel;
use Cowegis\Bundle\Contao\Model\Map\MapRepository;
use Netzmacht\Contao\Toolkit\Dca\Listener\AbstractListener;
use Netzmacht\Contao\Toolkit\Dca\Manager;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MapDcaListener extends AbstractListener
{
    protected static $name = 'tl_cowegis_map';

    /** @var TranslatorInterface */
    private $translator;

    /** @var SessionInterface */
    private $session;
    /**
     * @var MapRepository
     */
    private $mapRepository;

    public function __construct(
        Manager $dcaManager,
        MapRepository $mapRepository,
        TranslatorInterface $translator,
        SessionInterface $session
    ) {
        parent::__construct($dcaManager);

        $this->mapRepository = $mapRepository;
        $this->translator    = $translator;
        $this->session       = $session;
    }

    public function layerList($records, string $uniqueId) : string
    {
        $strReturn = '';

        while ($records->next()) {
            $strReturn .= '<li>' . $records->name . ' (ID: ' . $records->id . ')' . '</li>';
        }

        return '<ul id="sort_' . $uniqueId . '">' . $strReturn . '</ul>';
    }

    /**
     * Add warnings for incomplete configurations.
     *
     * @param DataContainer $dataContainer The data container driver.
     *
     * @return void
     */
    public function showIncompleteConfigurationWarning($dataContainer) : void
    {
        if (Input::get('act') !== 'edit') {
            return;
        }

        $mapModel = $this->mapRepository->find((int) $dataContainer->id);
        if (! $mapModel instanceof MapModel) {
            return;
        }

        if ($mapModel->zoom === null || $mapModel->zoom === '') {
            $this->session->getFlashBag()->add(
                'contao.BE.info',
                $this->translator->trans('ERR.cowegisMissingZoomLevel', [], 'contao_default')
            );
        }
    }
}
