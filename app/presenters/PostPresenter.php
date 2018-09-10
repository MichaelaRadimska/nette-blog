<?php declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use stdClass;


class PostPresenter extends Presenter
{
    /** @var Nette\Database\Context */
    private $database;

    public function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
    }

    public function renderShow($postId)
    {
        $this->template->post = $this->database->table('posts')->get($postId);
        $post = $this->template->post;
        $this->template->comments = $post->related('comment')->order('created_at DESC');
    }

    protected function createComponentCommentForm()
    {
        $form = new Form; //means Nette\Application\UI\Form
        $form->addText('name', 'Jméno:')
            ->setRequired("Uveďte prosím své jméno nebo přezdívku.");
        $form->addEmail('email', 'E-mail: ');
        $form->addTextArea('content', 'Komentář: ')
            ->setRequired('Napište prosím komentář.');
        $form->addSubmit('send', 'Publikovat komentář');
        $form->onSuccess[] = [$this, 'commentFormSucceeded'];

        return $form;
    }

    public function commentFormSucceeded(Form $form, stdClass $values)
    {
        $postId = $this->getParameter('postId');

        $this->database->table('comments')->insert([
            'post_id' => $postId,
            'name' => $values->name,
            'email' => $values->email,
            'content' => $values->content,
        ]);

        $this->flashMessage('Děkuji za komentář', 'success');
        $this->redirect('this');

    }

    public function createComponentPostForm()
    {
        $form = new Form;
        $form->addText('title', 'Název knihy:')
            ->setRequired('Uveďte prosím název knihy.');
        $form->addTextArea('content', 'Obsah:')
            ->setRequired('Napište prosím obsah knihy.');
        $form->addTextArea('evaluation', 'Dojem:')
            ->setRequired('Napište prosím svůj dojem z knihy.');
        $form->addSubmit('send', 'Uložit a publikovat');
        $form->onSuccess[] = [$this, 'postFormSucceeded'];
        return $form;
    }

    public function postFormSucceeded(Form $form, stdClass $values)
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->error('Pro vytvoření, nebo editování příspěvku se musíte přihlásit.');
        }

        $postId = $this->getParameter('postId');

        if ($postId) {
            $post = $this->database->table('posts')->get($postId);
            $post->update($values);
        } else {
            $post = $this->database->table('posts')->insert($values);
        }

        $this->flashMessage('Příspěvek byl úspěšně publikován.', 'success');
        $this->redirect('show', $post->id);

    }

    public function actionEdit($postId): void
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
        $post = $this->database->table('posts')->get($postId);
        if (!$post) {
            $this->error('Příspěvek nebyl nalezen');
        }
        $this['postForm']->setDefaults($post->toArray());
    }


    public function actionCreate()
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
    }
}