<?php

namespace Sigma\UpdatingCustomerAttribute\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpdateCustomerGivexAttribute implements DataPatchInterface, PatchRevertableInterface
{
    private $eavSetupFactory;
    private $moduleDataSetup;

    public function __construct(
        EavSetupFactory $eavSetupFactory,
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        // Change the backend_type of your custom attribute
        $eavSetup->updateAttribute(
            Customer::ENTITY,
            'givex_number',
            'backend_type',
            'varchar'
        );

        $this->moduleDataSetup->endSetup();
    }

    public function revert()
    {
        $this->moduleDataSetup->startSetup();

        // Implement the revert logic here if necessary.

        $this->moduleDataSetup->endSetup();
    }
}