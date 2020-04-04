<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeStockImage\Test\Unit\Model;

use Magento\AdobeStockAssetApi\Api\SaveAssetInterface;
use Magento\AdobeStockImage\Model\Extract\AdobeStockAsset as DocumentToAsset;
use Magento\AdobeStockImage\Model\Extract\Keywords as DocumentToKeywords;
use Magento\AdobeStockImage\Model\SaveImageFile;
use Magento\AdobeStockImage\Model\SaveMediaGalleryAsset;
use Magento\AdobeStockImage\Model\SaveImage;
use Magento\AdobeStockImage\Model\SetLicensedInMediaGalleryGrid;
use Magento\Framework\Api\Search\Document;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\MediaGalleryApi\Model\Keyword\Command\SaveAssetKeywordsInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test for Save image model.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveImageTest extends TestCase
{
    /**
     * @var MockObject|SaveAssetInterface
     */
    private $saveAdobeStockAsset;

    /**
     * @var MockObject|DocumentToAsset
     */
    private $documentToAsset;

    /**
     * @var MockObject|DocumentToKeywords
     */
    private $documentToKeywords;

    /**
     * @var MockObject|SaveAssetKeywordsInterface
     */
    private $saveAssetKeywords;

    /**
     * @var SetLicensedInMediaGalleryGrid|MockObject
     */
    private $setLicensedInMediaGalleryGridMock;

    /**
     * @var SaveImageFile|MockObject
     */
    private $saveImageFileMock;

    /**
     * @var SaveMediaGalleryAsset|MockObject
     */
    private $saveMediaGalleryAssetMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var SaveImage
     */
    private $saveImage;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->saveAdobeStockAsset = $this->createMock(SaveAssetInterface::class);
        $this->documentToAsset = $this->createMock(DocumentToAsset::class);
        $this->documentToKeywords = $this->createMock(DocumentToKeywords::class);
        $this->saveAssetKeywords = $this->createMock(SaveAssetKeywordsInterface::class);
        $this->setLicensedInMediaGalleryGridMock = $this->createMock(SetLicensedInMediaGalleryGrid::class);
        $this->saveImageFileMock = $this->createMock(SaveImageFile::class);
        $this->saveMediaGalleryAssetMock = $this->createMock(SaveMediaGalleryAsset::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->saveImage = (new ObjectManager($this))->getObject(
            SaveImage::class,
            [
                'saveAdobeStockAsset' =>  $this->saveAdobeStockAsset,
                'documentToAsset' =>  $this->documentToAsset,
                'saveAssetKeywords' => $this->saveAssetKeywords,
                'documentToKeywords' => $this->documentToKeywords,
                'setLicensedInMediaGalleryGrid' => $this->setLicensedInMediaGalleryGridMock,
                'saveImageFile' => $this->saveImageFileMock,
                'saveMediaGalleryAsset' => $this->saveMediaGalleryAssetMock,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * Verify that image from the Adobe Stock can be saved.
     *
     * @param Document $document
     * @param string $url
     * @param string $destinationPath
     * @dataProvider assetProvider
     */
    public function testExecute(Document $document, string $url, string $destinationPath): void
    {
        $mediaGalleryAssetId = 42;
        $keywords = [];

        $this->saveImageFileMock->expects($this->once())
            ->method('execute')
            ->with($document, $url, $destinationPath);

        $this->saveMediaGalleryAssetMock->expects($this->once())
            ->method('execute')
            ->with($document, $destinationPath)
            ->willReturn($mediaGalleryAssetId);

        $this->documentToKeywords->expects($this->once())
            ->method('convert')
            ->with($document)
            ->willReturn($keywords);

        $this->saveAssetKeywords->expects($this->once())
            ->method('execute')
            ->with($keywords, $mediaGalleryAssetId);

        $this->documentToAsset->expects($this->once())
            ->method('convert')
            ->with($document, ['media_gallery_id' => $mediaGalleryAssetId]);

        $this->saveAdobeStockAsset->expects($this->once())
            ->method('execute');

        $this->setLicensedInMediaGalleryGridMock->expects($this->once())
            ->method('execute');

        $this->saveImage->execute($document, $url, $destinationPath);
    }

    /**
     * Test save image with exception.
     *
     * @param Document $document
     * @param string $url
     * @param string $destinationPath
     * @dataProvider assetProvider
     */
    public function testSaveImageWithException(Document $document, string $url, string $destinationPath): void
    {
        $this->saveImageFileMock->expects($this->once())
            ->method('execute')
            ->with($document, $url, $destinationPath)
            ->willThrowException(new \Exception('Some Exception'));

        $this->expectException(CouldNotSaveException::class);

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->willReturnSelf();

        $this->saveImage->execute($document, $url, $destinationPath);
    }

    /**
     * Data provider for testExecute
     *
     * @return array
     */
    public function assetProvider(): array
    {
        return [
            [
                'document' => $this->getDocument(),
                'url' => 'https://as2.ftcdn.net/jpg/500_FemVonDcttCeKiOXFk.jpg',
                'destinationPath' => 'path'
            ]
        ];
    }

    /**
     * Get document mock object.
     *
     * @return MockObject
     */
    private function getDocument(): MockObject
    {
        return $this->createMock(Document::class);
    }
}
