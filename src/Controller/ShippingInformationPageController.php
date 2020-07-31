<?php

declare(strict_types=1);

namespace VaaChar\VaaCharSyliusShippingInformationPagePlugin\Controller;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Addressing\Model\ZoneInterface;
use Sylius\Component\Addressing\Model\ZoneMemberInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\Component\Core\Repository\ShippingMethodRepositoryInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class ShippingInformationPageController extends AbstractController
{
    /** @var ChannelContextInterface */
    private $channelContext;

    /** @var LocaleContextInterface */
    private $localeContext;

    /** @var ShippingMethodRepositoryInterface */
    private $shippingMethodRepository;

    public function __construct(
        ChannelContextInterface $channelContext,
        LocaleContextInterface $localeContext,
        ShippingMethodRepositoryInterface $shippingMethodRepository
    ) {
        $this->channelContext = $channelContext;
        $this->localeContext = $localeContext;
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

        $localeCode = $this->localeContext->getLocaleCode();

        $shippingMethodsWithResolvedMembers = [];

        /** @var ShippingMethodInterface $shippingMethod */
        foreach ($shippingMethods as $shippingMethod) {
            $shippingMethodData = [];

            $shippingMethodTranslation = $shippingMethod->getTranslation($localeCode);
            $shippingMethodData['name'] = $shippingMethodTranslation->getName();

            $shippingMethodConfiguration = $shippingMethod->getConfiguration()[$channel->getCode()] ?? null;
            if ($shippingMethodConfiguration === null) {
                continue;
            }

            $shippingMethodData['amount'] = $shippingMethodConfiguration['amount'];

            $shippingMethodZone = $shippingMethod->getZone();
            if ($shippingMethodZone === null) {
                continue;
            }

            $shippingMethodMembers = $this->resolveMembersForZone($shippingMethodZone);
            $shippingMethodData['members'] = $shippingMethodMembers;

            $shippingMethodsWithResolvedMembers[] = $shippingMethodData;
        }

        return $this->render(
            '@VaaCharSyliusShippingInformationPagePlugin/show.html.twig',
            ['shippingMethodsWithResolvedMembers' => $shippingMethodsWithResolvedMembers]
        );
    }

    /** @return Collection|ZoneMemberInterface[] */
    protected function resolveMembersForZone(ZoneInterface $zone): Collection {
        $zoneType = $zone->getType();

        switch ($zoneType) {
            case 'country':
                return $this->getMembersForCountryZone($zone);
                break;

            case 'province':
                return $this->getMembersForProvinceZone($zone);
                break;

            case 'zone':
                return $this->getMembersForZoneOfZones($zone);
                break;
        }
    }

    /** @return Collection|ZoneMemberInterface[] */
    protected function getMembersForCountryZone(ZoneInterface $zone): Collection {
        return $zone->getMembers();
    }

    /** @return Collection|ZoneMemberInterface[] */
    protected function getMembersForProvinceZone(ZoneInterface $zone): Collection {
        return $zone->getMembers();
    }

    /** @return Collection|ZoneMemberInterface[] */
    protected function getMembersForZoneOfZones(ZoneInterface $zone): Collection {
        $membersCollection = new Collection();

        /** @var ZoneMemberInterface $zoneMember */
        foreach ($zone->getMembers() as $zoneMember) {
            $zone = $zoneMember->getBelongsTo();

            if ($zone === null) {
                continue;
            }

            $membersForZone = $this->resolveMembersForZone($zone);
            foreach ($membersForZone as $memberForZone) {
                $membersCollection->add($memberForZone);
            }
        }

        return $membersCollection;
    }
}
