<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Doctrine\Common\Util\Debug,
    Gedmo\Translatable\Translatable,
    Gedmo\Translatable\Entity\Translation,
    Gedmo\Translatable\TranslationListener,
    Sluggable\Fixture\TransArticleManySlug;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Sluggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableManySlugTest extends BaseTestCaseORM
{
    private $articleId;
    private $translationListener;

    const ARTICLE = 'Sluggable\\Fixture\\TransArticleManySlug';
    const TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $this->translationListener = new TranslationListener();
        $this->translationListener->setTranslatableLocale('en_us');
        $evm->addEventSubscriber(new SluggableListener);
        $evm->addEventSubscriber($this->translationListener);

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testSlugAndTranslation()
    {
        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $this->assertTrue($article instanceof Translatable && $article instanceof Sluggable);
        $this->assertEquals($article->getSlug(), 'the-title-my-code');
        $this->assertEquals($article->getUniqueSlug(), 'the-unique-title');
        $repo = $this->em->getRepository(self::TRANSLATION);

        $translations = $repo->findTranslations($article);
        $this->assertEquals(count($translations), 1);
        $this->assertArrayHasKey('en_us', $translations);
        $this->assertEquals(3, count($translations['en_us']));

        $this->assertArrayHasKey('slug', $translations['en_us']);
        $this->assertEquals('the-title-my-code', $translations['en_us']['slug']);

        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $article->setTranslatableLocale('de_de');
        $article->setCode('code in de');
        $article->setTitle('title in de');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::TRANSLATION);
        $translations = $repo->findTranslations($article);
        $this->assertEquals(count($translations), 2);
        $this->assertArrayHasKey('de_de', $translations);
        $this->assertEquals(3, count($translations['de_de']));

        $this->assertEquals('title in de', $translations['de_de']['title']);

        $this->assertArrayHasKey('slug', $translations['de_de']);
        $this->assertEquals('title-in-de-code-in-de', $translations['de_de']['slug']);
    }



    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
            self::TRANSLATION
        );
    }

    private function populate()
    {
        $article = new TransArticleManySlug();
        $article->setTitle('the title');
        $article->setCode('my code');
        $article->setUniqueTitle('the unique title');
        

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        $this->articleId = $article->getId();
    }
}
