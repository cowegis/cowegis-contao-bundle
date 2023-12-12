<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Dca;

use Contao\Database\Result;
use Contao\DataContainer;
use Contao\Input;
use Contao\Model\Collection;
use Cowegis\Bundle\Contao\Model\Map\MapModel;
use Cowegis\Bundle\Contao\Model\Map\MapRepository;
use Netzmacht\Contao\Toolkit\Dca\Listener\AbstractListener;
use Netzmacht\Contao\Toolkit\Dca\Manager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MapDcaListener extends AbstractListener
{
    /** @var string */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected static $name = 'tl_cowegis_map';

    public function __construct(
        Manager $dcaManager,
        private readonly MapRepository $mapRepository,
        private readonly TranslatorInterface $translator,
        private SessionInterface $session,
    ) {
        parent::__construct($dcaManager);
    }

    /** @param Result|Collection $records */
    public function layerList($records, string $uniqueId): string
    {
        $strReturn = '';

        while ($records->next()) {
            $strReturn .= '<li>' . $records->name . ' (ID: ' . $records->id . ') </li>';
        }

        return '<ul id="sort_' . $uniqueId . '">' . $strReturn . '</ul>';
    }

    /**
     * Add warnings for incomplete configurations.
     *
     * @param DataContainer $dataContainer The data container driver.
     */
    public function showIncompleteConfigurationWarning(DataContainer $dataContainer): void
    {
        if (Input::get('act') !== 'edit') {
            return;
        }

        $mapModel = $this->mapRepository->find((int) $dataContainer->id);
        if (! $mapModel instanceof MapModel) {
            return;
        }

        if ($mapModel->zoom !== null && $mapModel->zoom !== '') {
            return;
        }

        if (! $this->session instanceof Session) {
            return;
        }

        $this->session->getFlashBag()->add(
            'contao.BE.info',
            $this->translator->trans('ERR.cowegisMissingZoomLevel', [], 'contao_default'),
        );
    }
}
