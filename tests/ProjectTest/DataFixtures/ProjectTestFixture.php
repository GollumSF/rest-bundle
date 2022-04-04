<?php
namespace Test\GollumSF\RestBundle\ProjectTest\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Test\GollumSF\RestBundle\ProjectTest\Entity\Author;
use Test\GollumSF\RestBundle\ProjectTest\Entity\Book;
use Test\GollumSF\RestBundle\ProjectTest\Entity\Category;

class ProjectTestFixture extends Fixture {

	public function load(ObjectManager $manager) {

		$categories = [];
		for ($i = 1; $i <= 40; $i++) {
			$category = (new Category());
			$manager->persist($category);
			$categories[] = $category;
		}
		$manager->flush();

		$authors = [];
		for ($i = 1; $i <= 40; $i++) {
			$author = (new Author())
				->setName('AUTHOR_'.$i)
			;
			$manager->persist($author);
			$authors[] = $author;
		}
		$manager->flush();

		$iAuthor = 0;
		$repeatAuthor = 0;
		$maxRepeatAuthor = 1;

		$iCategory = 0;
		$repeatCategory = 0;
		$maxRepeatCategory = 3;
		
		for ($i = 1; $i <= 50; $i++) {
			$book = (new Book())
				->setTitle('TITLE_'.$i)
				->setDescription('DESCRIPTION_'.$i)
				->setAuthor($authors[$iAuthor])
				->setCategory($categories[$iCategory])
			;
			$manager->persist($book);
			
			$repeatAuthor++;
			if ($repeatAuthor === $maxRepeatAuthor) {
				$iAuthor++;
				$repeatAuthor = 0;
				$maxRepeatAuthor++;
				if ($maxRepeatAuthor === 3) {
					$maxRepeatAuthor = 1;
				}
			}

			$repeatCategory++;
			if ($repeatCategory === $maxRepeatCategory) {
				$iCategory++;
				$repeatCategory = 0;
				$maxRepeatCategory++;
				if ($maxRepeatCategory === 4) {
					$maxRepeatCategory = 1;
				}
			}
			
		}
		$manager->flush();
	}
}
