<?php

namespace Modules\SocialQuiz\Pages;

use Lightning\Tools\Configuration;
use Lightning\Tools\Request;
use Lightning\Tools\Template;
use Modules\SocialQuiz\Model\SocialQuiz;
use Overridable\Lightning\Tools\Security\Encryption;
use Source\View\Page;

class Results extends Page {

    protected $quiz;

    public function hasAccess() {
        return true;
    }

    public function get() {
        if ($name = Request::get('q')) {
            $this->quiz = SocialQuiz::loadByName($name);
        }

        if ($encrypted = Request::get('ep', Request::TYPE_ENCRYPTED)) {
            $decrypted = Encryption::aesDecrypt($encrypted, Configuration::get('user.key'));
            $decrypted = json_decode($decrypted, true);
            if (!empty($decrypted['q'])) {
                $this->quiz = SocialQuiz::loadByName($decrypted['q']);
            }
            if (!empty($decrypted['score'])) {
                $score = intval($decrypted['score']);
            }
        }

        if (empty($this->quiz)) {
            throw new \Exception('Invalid Quiz');
        }

        $template = Template::getInstance();
        $template->set('quiz', $this->quiz);
        $this->page = ['share', 'SocialQuiz'];
        if ($this->quiz->type == SocialQuiz::TYPE_POLL) {
            $this->quiz->updateResults();
        } else {
            // Quiz test type.
            $template->set('score', !empty($score) ? $score : 0);
        }
    }
}
