<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Action;

use Contao\CoreBundle\Fragment\FragmentOptionsAwareInterface;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FrontendTemplate;
use Contao\Model;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function array_merge;
use function array_unshift;
use function implode;
use function is_array;

abstract class AbstractFragmentAction implements FragmentOptionsAwareInterface
{
    /**
     * @var array
     */
    protected $options = [];

    /** @var ContaoFramework */
    protected $contaoFramework;

    public function __construct(ContaoFramework $contaoFramework)
    {
        $this->contaoFramework = $contaoFramework;
    }

    public function setFragmentOptions(array $options) : void
    {
        $this->options = $options;
    }

    protected function renderResponse(
        Request $request,
        Model $model,
        string $templateName,
        string $section,
        array $classes = []
    ) : Response {
        if ($model->customTpl) {
            $templateName = $model->customTpl;
        } elseif (isset($this->options['template'])) {
            $templateName = $this->options['template'];
        }

        array_unshift($classes, $templateName);
        /** @var FrontendTemplate $template */
        $template = $this->contaoFramework->createInstance(FrontendTemplate::class, [$templateName]);
        $template->setData($this->getTemplateData($model, $section, $classes));

        return $template->getResponse();
    }

    protected function getTemplateData(Model $model, string $section, array $classes = []) : array
    {
        return array_merge(
            $model->row(),
            ['inColumn' => $section],
            $this->compileCssAttributes($model->cssID, $classes ?? []),
            $this->compileHeadline($model->headline)
        );
    }

    /**
     * @param string|array $headline
     */
    protected function compileHeadline($headline) : array
    {
        $data = StringUtil::deserialize($headline);

        if (is_array($data)) {
            return [
                'headline' => $data['value'],
                'hl'       => $data['unit'],
            ];
        }

        return [
            'headline' => $data,
            'hl'       => 'h1',
        ];
    }

    /**
     * @param string|array $cssID
     */
    protected function compileCssAttributes($cssID, array $classes = []) : array
    {
        $data = StringUtil::deserialize($cssID, true);
        if (!empty($data[1])) {
            array_unshift($classes, $data[1]);
        }

        return [
            'class' => implode(' ', $classes),
            'cssID' => empty($data[0]) ? '' : ' id="' . $data[0] . '"',
        ];
    }

    abstract protected function getType() : string;
}
