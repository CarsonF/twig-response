<?php declare(strict_types=1);

namespace Gmo\Web\EventListener;

use Gmo\Web\Response\TemplateResponse;
use Gmo\Web\Response\TemplateView;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

/**
 * Converts controller results that are TemplateView's to TemplateResponse's.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class TemplateViewListener implements EventSubscriberInterface
{
    /** @var Environment|null */
    protected $twig;

    /**
     * Constructor.
     *
     * @param Environment|null $twig
     */
    public function __construct(?Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * If controller result is a TemplateView, convert it to a TemplateResponse.
     *
     * @param GetResponseForControllerResultEvent $event
     */
    public function onView(GetResponseForControllerResultEvent $event)
    {
        $result = $event->getControllerResult();

        if (!$result instanceof TemplateView) {
            return;
        }

        $format = $event->getRequest()->getRequestFormat();

        if ($format === 'json') {
            $event->setControllerResult($result->getContext());

            return;
        } elseif ($format !== 'html') {
            return;
        }

        if ($response = $this->render($result)) {
            $event->setResponse($response);
        }
    }

    /**
     * Render TemplateView to a TemplateResponse.
     *
     * @param TemplateView $view
     *
     * @return Response|null
     */
    public function render(TemplateView $view): ?Response
    {
        if (!$this->twig) {
            return null;
        }

        $content = $this->twig->render($view->getTemplate(), $view->getContext()->toArray());

        $response = new TemplateResponse($view->getTemplate(), $view->getContext(), $content);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['onView', 1], // before json
        ];
    }
}
