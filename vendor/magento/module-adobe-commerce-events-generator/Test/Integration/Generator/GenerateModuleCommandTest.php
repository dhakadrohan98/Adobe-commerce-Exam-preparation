<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsGenerator\Test\Integration\Generator;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventList;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventCode\EventCodeSupportedValidator;
use Magento\AdobeCommerceEventsGenerator\Console\Command\GenerateModule\Generator;
use Magento\AdobeCommerceEventsGenerator\Console\Command\GenerateModuleCommand;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Checks that GenerateModuleCommand command generates plugins based on the event list
 */
class GenerateModuleCommandTest extends TestCase
{
    /**
     * @var string
     */
    private $outputDir;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var EventList|MockObject
     */
    private $eventListMock;

    /**
     * @var EventCodeSupportedValidator|MockObject
     */
    private $validatorMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->outputDir = __DIR__ . '/../../_generated';
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->outputDir);

        $this->objectManager = Bootstrap::getObjectManager();
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->eventListMock = $this->createPartialMock(EventList::class, ['getAll']);
        $this->validatorMock = $this->createMock(EventCodeSupportedValidator::class);
        $this->directoryListMock->expects(self::once())
            ->method('getPath')
            ->willReturn($this->outputDir);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        $this->filesystem->remove($this->outputDir);
    }

    /**
     * Checks that command returns an error when event is not in the list of supported events
     *
     * @return void
     * @throws \Exception
     */
    public function testGeneratePluginEventIsNotSupported(): void
    {
        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([
                new Event('plugin.magento.catalog.model.resource_model.product.validate'),
                new Event('observer.magento.catalog.model.resource_model.product.validate'),
            ]);

        $commandTester = new CommandTester($this->getGenerateCommand(false));
        $commandTester->execute([]);

        self::assertNotEquals(0, $commandTester->getStatusCode());
        self::assertStringContainsString(
            'Event "plugin.magento.catalog.model.resource_model.product.validate"' .
            ' is not defined in the list of supported events',
            $commandTester->getDisplay()
        );
    }

    /**
     * Checks that command returns an error when class for event code can not be found
     *
     * @return void
     * @throws \Exception
     */
    public function testGeneratePluginResourceModelClassNotExists(): void
    {
        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([
                new Event('plugin.magento.theme.model.resource_model.class_not_exist.save'),
            ]);

        $commandTester = new CommandTester($this->getGenerateCommand());
        $commandTester->execute([]);

        self::assertNotEquals(0, $commandTester->getStatusCode());
        self::assertStringContainsString(
            'Event "plugin.magento.theme.model.resource_model.class_not_exist.save" is not defined',
            $commandTester->getDisplay()
        );
    }

    /**
     * Checks that command returns an error when api interface for event code can not be found
     *
     * @return void
     * @throws \Exception
     */
    public function testGeneratePluginApiInterfaceNotExists(): void
    {
        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([
                new Event('plugin.magento.theme.api.interface_not_exists.save'),
            ]);

        $commandTester = new CommandTester($this->getGenerateCommand());
        $commandTester->execute([]);

        self::assertNotEquals(0, $commandTester->getStatusCode());
        self::assertStringContainsString(
            'Event "plugin.magento.theme.api.interface_not_exists.save" is not defined',
            $commandTester->getDisplay()
        );
    }

    /**
     * Checks module generation
     *
     * @return void
     * @throws \Exception
     */
    public function testGenerate(): void
    {
        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([
                new Event('plugin.magento.customer.api.customer_repository.save'),
                new Event('plugin.magento.customer.model.resource_model.group.save'),
                new Event('plugin.magento.user.model.resource_model.user.save'),
                new Event('observer.catalog_product_save_after'),
            ]);

        $commandTester = new CommandTester($this->getGenerateCommand(false));
        $commandTester->execute([]);

        self::assertEquals(0, $commandTester->getStatusCode());

        $moduleBaseDir = $this->outputDir . '/code/Magento/AdobeCommerceEvents';
        $this->moduleRunBaseAssertions($moduleBaseDir);

        self::assertTrue($this->filesystem->exists($moduleBaseDir . '/Plugin/Customer/Api'));
        self::assertTrue($this->filesystem->exists($moduleBaseDir . '/Plugin/Customer/ResourceModel'));
        self::assertTrue($this->filesystem->exists($moduleBaseDir . '/Plugin/User/ResourceModel'));
        self::assertEquals(4, substr_count(file_get_contents($moduleBaseDir . '/etc/di.xml'), '<plugin'));

        $pluginEventCodeList = file_get_contents($moduleBaseDir . '/EventCode/Plugin.php');

        self::assertEquals(3, substr_count($pluginEventCodeList, "' => '"));
        self::assertStringContainsString('magento.customer.api.customer_repository.save', $pluginEventCodeList);
        self::assertStringContainsString('magento.customer.model.resource_model.group.save', $pluginEventCodeList);
        self::assertStringContainsString('magento.user.model.resource_model.user.save', $pluginEventCodeList);
    }

    /**
     * Runs list of assertions to check that module was generated correctly
     *
     * @param string $moduleBaseDir
     * @return void
     */
    private function moduleRunBaseAssertions(string $moduleBaseDir): void
    {
        self::assertTrue($this->filesystem->exists($moduleBaseDir));
        self::assertTrue($this->filesystem->exists($moduleBaseDir . '/Plugin'));
        self::assertTrue($this->filesystem->exists($moduleBaseDir . '/Plugin/Framework'));
        self::assertTrue($this->filesystem->exists($moduleBaseDir . '/etc/di.xml'));
        self::assertTrue($this->filesystem->exists($moduleBaseDir . '/etc/module.xml'));
        self::assertTrue($this->filesystem->exists($moduleBaseDir . '/composer.json'));

        self::assertTrue($this->filesystem->exists($moduleBaseDir . '/EventCode/Plugin.php'));
        self::assertTrue($this->filesystem->exists($moduleBaseDir . '/EventCode/Observer.php'));

        $observerEventCodeList = file_get_contents($moduleBaseDir . '/EventCode/Observer.php');
        self::assertStringContainsString('observer.catalog_product_save_after', $observerEventCodeList);
    }

    /**
     * Returns generate command
     *
     * @param bool $mockValidator
     * @return GenerateModuleCommand
     */
    private function getGenerateCommand(bool $mockValidator = true): GenerateModuleCommand
    {
        $arguments = ['eventList' => $this->eventListMock];
        if ($mockValidator) {
            $arguments['eventCodeSupportedValidator'] = $this->validatorMock;
        }

        $generator = $this->objectManager->create(Generator::class, $arguments);

        return $this->objectManager->create(GenerateModuleCommand::class, [
            'generator' => $generator,
            'directoryList' => $this->directoryListMock
        ]);
    }
}
