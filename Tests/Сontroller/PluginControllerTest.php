<?php

namespace MauticPlugin\LeuchtfeuerCustomMenuItemsBundle\Tests\Сontroller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\Plugin;
use MauticPlugin\LeuchtfeuerCustomMenuItemsBundle\Integration\ApiCallsBundleIntegration;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PluginControllerTest extends MauticMysqlTestCase
{

    public function setUp(): void
    {
        parent::setUp();
        $this->createIntegration();
    }

    public function testRouteNotAccessibility(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/savemenuitem');

        Assert::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }


    public function testSaveMenuItemRouteAccessibility(): void
    {
        $this->client->request(
            Request::METHOD_POST,
            '/s/savemenuitem',
            $this->getPayload()
        );

        Assert::assertTrue($this->client->getResponse()->isOk());
    }

    public function testMenuItemRouteAccessibility(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/menuitem');

        Assert::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }


    public function testIfPluginIsEnabled(): void
    {
        $this->client->request(
            Request::METHOD_POST,
            '/s/savemenuitem',
            $this->getPayload()
        );

        $crawler =  $this->client->request(Request::METHOD_GET, '/s/');
        $node = $crawler->filterXPath("//*[text()='Test name']");
       $result = $node->text();
        Assert::assertSame('Test name', $result);
    }


    public function testIfPluginIsDisabled(): void
    {
        /** @var IntegrationsHelper $integrationsHelper */
        $integrationsHelper = static::getContainer()->get('mautic.integrations.helper');

        $this->client->request(
            Request::METHOD_POST,
            '/s/savemenuitem',
            $this->getPayload()
        );

        $integrationEntity = $integrationsHelper->getIntegration(ApiCallsBundleIntegration::INTEGRATION_NAME)->getIntegrationConfiguration()->setIsPublished(false);
        $integrationsHelper->saveIntegrationConfiguration($integrationEntity);

        $crawler =  $this->client->request(Request::METHOD_GET, '/s/');

        $this->assertStringNotContainsString(
            'Text name',
            $crawler->filter('body')->text()
        );
    }

    /**
     * @return array<mixed>
     */
    public function getPayload(): array
    {
        return [
            'items' => [
                [
                    "name" => "Test name",
                    "url" => "wwww.test.com",
                    "order" => 1,
                    "type" => "_blank",
                ]
            ]
        ];
    }


    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createIntegration(): void
    {
        $plugin = new Plugin();
        $plugin->setName('Custom Menu Items by Leuchtfeuer');
        $plugin->setBundle('LeuchtfeuerCustomMenuItemsBundle');
        $this->em->persist($plugin);

        $integration = new Integration();
        $integration->setPlugin($plugin);
        $integration->setIsPublished(true);
        $integration->setName('CustomMenuItems');
        $this->em->persist($integration);
        $this->em->flush();
    }
}