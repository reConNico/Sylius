<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\Bundle\ApiBundle\Validator\Constraints;

use Sylius\Bundle\ApiBundle\Command\Checkout\AddressOrder;
use Sylius\Component\Addressing\Model\CountryInterface;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Webmozart\Assert\Assert;

final class CorrectOrderAddressValidator extends ConstraintValidator
{
    /** @var RepositoryInterface */
    private $countryRepository;

    public function __construct(RepositoryInterface $countryRepository)
    {
        $this->countryRepository = $countryRepository;
    }

    public function validate($value, Constraint $constraint): void
    {
        /** @var AddressOrder $value */
        Assert::isInstanceOf($value, AddressOrder::class);

        /** @var CorrectOrderAddress $constraint */
        Assert::isInstanceOf($constraint, CorrectOrderAddress::class);

        $this->validateAddress($value->billingAddress, $constraint);
        $this->validateAddress($value->shippingAddress, $constraint);
    }

    private function validateAddress(?AddressInterface $address, CorrectOrderAddress $constraint): void
    {
        if ($address === null) {
            return;
        }

        /** @var string|null $countryCode */
        $countryCode = $address->getCountryCode();

        if ($countryCode === null) {
            $this->context->addViolation(
                $constraint->addressWithoutCountryCodeCanNotExistMessage
            );

            return;
        }

        /** @var CountryInterface|null $country */
        $country = $this->countryRepository->findOneBy(['code' => $countryCode]);

        if ($country === null) {
            $this->context->addViolation(
                $constraint->countryCodeNotExistMessage,
                ['%countryCode%' => $countryCode]
            );
        }
    }
}
