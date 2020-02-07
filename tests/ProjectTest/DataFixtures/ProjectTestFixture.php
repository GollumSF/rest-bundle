<?php
namespace Test\GollumSF\RestBundle\ProjectTest\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Test\GollumSF\RestBundle\ProjectTest\Entity\Book;

class ProjectTestFixture extends Fixture {

	public function load(ObjectManager $manager) {
		$books = [];
		for ($i = 1; $i <= 50; $i++) {
			$book = (new Book())
				->setTitle('TITLE_'.$i)
				->setDescription('DESCRIPTION_'.$i)
			;
			$manager->persist($book);
			$books[] = $book;
		}
		$manager->flush();
	}
}