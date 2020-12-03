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
use function assert;
use function implode;
use function is_array;

abstract class FragmentAction implements FragmentOptionsAwareInterface
{
    /**
     * @var array<string, mixed>
     */
    protected $options = [];

    /** @var ContaoFramework */
    protected $contaoFramework;

    public function __construct(ContaoFramework $contaoFramework)
    {
        $this->contaoFramework = $contaoFramework;
    }

    /** @param array<string,mixed> $options */
    public function setFragmentOptions(array $options): void
    {
        $this->options = $options;
    }

    /** @param string[]|null $classes */
    protected function renderResponse(
        Request $request,
        Model $model,
        string $templateName,
        string $section,
        array $classes = []
    ): Response {
        if ($model->customTpl) {
            $templateName = $model->customTpl;
        } elseif (isset($this->options['template'])) {
            $templateName = $this->options['template'];
        }

        array_unshift($classes, $templateName);
        $template = $this->contaoFramework->createInstance(FrontendTemplate::class, [$templateName]);
        assert($template instanceof FrontendTemplate);
        $template->setData($this->getTemplateData($model, $section, $classes));

        return $template->getResponse();
    }

    /**
     * @param string[]|null $classes
     *
     * @return array<string, mixed>
     */
    protected function getTemplateData(Model $model, string $section, array $classes = []): array
    {
        return array_merge(
            $model->row(),
            ['inColumn' => $section],
            $this->compileCssAttributes($model->cssID, $classes ?? []),
            $this->compileHeadline($model->headline)
        );
    }

    /**
     * @param string|string[] $headline
     *
     * @return array<string, string>
     */
    protected function compileHeadline($headline): array
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
     * @param string|string[] $cssID
     * @param string[]        $classes
     *
     * @return array<string, string>
     */
    protected function compileCssAttributes($cssID, array $classes = []): array
    {
        $data = StringUtil::deserialize($cssID, true);
        if (! empty($data[1])) {
            array_unshift($classes, $data[1]);
        }

        return [
            'class' => implode(' ', $classes),
            'cssID' => empty($data[0]) ? '' : ' id="' . $data[0] . '"',
        ];
    }

    abstract protected function getType(): string;
}
