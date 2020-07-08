<?php

declare(strict_types=1);

namespace VaaChar\VaaCharSyliusShippingInformationPagePlugin\Controller;

use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Repository\ShippingMethodRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class ShippingInformationPageController extends AbstractController
{
    /** @var ChannelContextInterface */
    private $channelContext;

    /** @var ShippingMethodRepositoryInterface */
    private $shippingMethodRepository;

    public function __construct(
        ChannelContextInterface $channelContext,
        ShippingMethodRepositoryInterface $shippingMethodRepository
    ) {
        $this->channelContext = $channelContext;
        $this->shippingMethodRepository = $shippingMethodRepository;
    }

    /**
     * @param string|null $name
     *
     * @return Response
     */
    public function showAction(): Response
    {
        $channel = $this->channelContext->getChannel();
        $shippingMethods = $this->shippingMethodRepository->findEnabledForChannel($channel);

        return $this->render(
            '@VaaCharSyliusShippingInformationPagePlugin/show.html.twig',
            ['shippingMethods' => $shippingMethods]
        );
    }
}
