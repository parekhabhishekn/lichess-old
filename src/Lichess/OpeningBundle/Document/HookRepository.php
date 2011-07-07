<?php

namespace Lichess\OpeningBundle\Document;

use Doctrine\ODM\MongoDB\DocumentRepository;

class HookRepository extends DocumentRepository
{
    public function findOneByOwnerId($id)
    {
        return $this->createQueryBuilder()
            ->field('ownerId')->equals($id)
            ->getQuery()
            ->getSingleResult();
    }

    public function findOneOpenById($id)
    {
        return $this->createQueryBuilder()
            ->field('id')->equals($id)
            ->field('match')->equals(false)
            ->getQuery()
            ->getSingleResult();
    }

    public function findAllOpen()
    {
        return $this->createQueryBuilder()
            ->field('match')->equals(false)
            ->sort('createdAt', 'asc')
            ->getQuery()
            ->execute();
    }

    public function removeOldHooks()
    {
        $old = new \DateTime('-1 hour');

        $this->createQueryBuilder()
            ->field('createdAt')->lt($old)
            ->remove()
            ->getQuery()
            ->execute();
    }
}
