<?php

namespace Application\ForumBundle\Controller;

use Herzult\Bundle\ForumBundle\Controller\PostController as BasePostController;
use Herzult\Bundle\ForumBundle\Model\Topic;
use Herzult\Bundle\ForumBundle\Model\Post;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;

class PostController extends BasePostController
{
    public function recentAction()
    {
        $html = $this->get('lichess_forum.newposts_cache')->getCache();

        if (!$html) {
            $qty = 30;
            $posts = array_reverse(iterator_to_array($this->get('herzult_forum.repository.post')->createQueryBuilder()
                ->sort('createdAt', 'DESC')
                ->limit($qty)
                ->getQuery()
                ->execute()));
            $posts = array_filter($posts, function($post) { return !$post->isStaff(); });

            $html = $this->get('templating')->render('HerzultForumBundle:Post:recent.html.twig', array('posts' => $posts));
            $this->get('lichess_forum.newposts_cache')->setCache($html);
        }

        return new Response($html);
    }

    public function newAction(Topic $topic)
    {
        $post = $this->get('herzult_forum.repository.post')->createNewPost();
        $this->get('lichess_forum.authorname_persistence')->loadPost($post);
        $form = $this->get('form.factory')->createNamed($this->get('lichess_forum.form_type.post'), 'forum_post_form', $post);

        return $this->get('templating')->renderResponse('HerzultForumBundle:Post:new.html.'.$this->getRenderer(), array(
            'form'  => $form->createView(),
            'topic' => $topic,
        ));
    }

    public function createAction(Topic $topic)
    {
        $post = $this->get('herzult_forum.repository.post')->createNewPost();
        $post->setTopic($topic);
        $this->get('herzult_forum.blamer.post')->blame($post);

        $form = $this->get('form.factory')->createNamed($this->get('lichess_forum.form_type.post'), 'forum_post_form', $post);
        $form->bindRequest($this->get('request'));

        if(!$form->isValid()) {
            return $this->invalidCreate($topic);
        }

        $this->get('herzult_forum.creator.post')->create($post);

        $objectManager = $this->get('herzult_forum.object_manager');
        $objectManager->persist($post);
        $objectManager->flush();
        $this->get('lichess_forum.newposts_cache')->invalidate();

        $url = $this->get('herzult_forum.router.url_generator')->urlForPost($post);
        $response = new RedirectResponse($url);
        $this->get('lichess_forum.authorname_persistence')->persistPost($post, $response);

        return $response;
    }

    protected function invalidCreate(Topic $topic)
    {
        $lastPage = $this->get('herzult_forum.router.url_generator')->getTopicNumPages($topic);

        return $this->forward('HerzultForumBundle:Topic:show', array(
            'categorySlug' => $topic->getCategory()->getSlug(),
            'slug'         => $topic->getSlug(),
            'id'           => $topic->getId()
        ), array('page' => $lastPage));
    }

    /* Override */
    public function deleteAction($id)
    {
        $post = $this->get('herzult_forum.repository.post')->find($id);
        if (!$post) {
            throw new NotFoundHttpException(sprintf('No post found with id "%s"', $id));
        }

        $precedentPost = $this->get('herzult_forum.repository.post')->getPostBefore($post);
        $this->get('herzult_forum.remover.post')->remove($post);
        $this->get('herzult_forum.object_manager')->flush();

        // that's why it's overriden
        $this->get('lichess_forum.newposts_cache')->invalidate();

        return new RedirectResponse($this->get('herzult_forum.router.url_generator')->urlForPost($precedentPost));
    }
}
