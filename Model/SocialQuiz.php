<?php

namespace Overridable\Modules\SocialQuiz\Model;

use Lightning\Model\Object;
use Lightning\Tools\ClientUser;
use Lightning\Tools\Database;
use Lightning\Tools\Request;
use Lightning\Tools\Session;
use Lightning\View\Field\BasicHTML;
use stdClass;

class SocialQuiz extends Object {
    const TABLE = 'social_quiz';
    const PRIMARY_KEY = 'quiz_id';

    const TYPE_TEST = 1;
    const TYPE_POLL = 2;

    protected $questions;
    protected $position;
    protected $data;

    public static function loadByName($name) {
        if ($quiz = Database::getInstance()->selectRow('social_quiz', ['quiz_name' => $name])) {
            $quiz = new static($quiz);
            $quiz->loadQuestions();
            return $quiz;
        }
        return null;
    }

    public static function loadByID($id) {
        if ($quiz = parent::loadByID($id)) {
            $quiz->loadQuestions();
            return $quiz;
        }
        return null;
    }

    public function loadQuestions($force = false) {
        if ($force || empty($this->questions)) {
            $this->questions = Database::getInstance()->selectAll('social_quiz_question', ['quiz_id' => $this->id]);
        }
    }

    public function setQuestionPosition($position) {
        $this->position = $position;
    }

    public function getQuestionPosition() {
        return $this->position;
    }

    public function getQuestion() {
        return $this->questions[$this->position]['question'];
    }

    public function getQuestionCount() {
        return count($this->questions);
    }

    public function getQuestionID() {
        return $this->questions[$this->position]['question_id'];
    }

    public function getAnswers() {
        $answers = json_decode($this->questions[$this->position]['answers'], true);
        if (is_array(current($answers))) {
            if (empty($this->data->answerSplit)) {
                $this->data->answerSplit = new stdClass();
            }
            if (empty($this->data->answerSplit->{$this->position})) {
                $this->data->answerSplit->{$this->position} = rand(0, count($answers) - 1);
                $this->saveData();
            }
            $answers = $answers[$this->data->answerSplit->{$this->position}];
        }
        return $answers;
    }

    /**
     * Get the scare as a percentage of 100 when in test quiz mode.
     */
    public function getScore() {
        $this->loadQuestions();
        $correct = 0;
        foreach ($this->questions as $question) {
            if ($this->data->answers[$question['question_id']]->value === $question['answer']) {
                $correct ++;
            }
        }
        return 100 * $correct / count($this->questions);
    }

    /**
     * Render the answer options as form elements.
     * @return string
     */
    public function renderOptions() {
        $options = $this->getAnswers();
        switch ($this->questions[$this->position]['type']) {
            case 'select':
                return BasicHTML::select('answer', $options, null, ['required' => true]);
                break;
            case 'radio':
            default:
                return BasicHTML::radioGroup('answer', $options, null, ['required' => true]);
        }
    }

    public function getAnswerValue($value) {
        $set = 0;
        $answers = json_decode($this->questions[$this->position]['answers'], true);
        if (is_array(current($answers))) {
            $set = $this->data->answerSplit->{$this->position};
        }
        return [
            'set' => $set,
            'value' => $value,
        ];
    }

    public function setData($data) {
        $this->data = $data;
    }

    /**
     * Save the data to the session.
     */
    public function saveData() {
        $session = Session::getInstance();
        if (empty($session->content->quiz_data)) {
            $session->content->quiz_data = new stdClass();
        }
        $session->content->quiz_data->{$this->quiz_name} = $this->data;
        $session->save();
    }

    /**
     * Save the answers as complete quizes to database.
     */
    public function saveResults() {
        $answers = $this->data->answers;
        Database::getInstance()->insert('social_quiz_result', [
            'quiz_id' => $this->quiz_id,
            'user_id' => ClientUser::getInstance()->id,
            'results' => json_encode($answers),
            'time' => time(),
            'ip_address' => Request::server(Request::IP),
        ]);
    }

    public function updateResults() {
        // This will store the calculated results in the format:
        // $aggregated_results = [
        //     result pattern, determined by which sequence of questions were asked
        //     in each set of 2 numbers, the first is the question id, the second is the option set id
        //     '1:0-2:0-3:0-4:1' => [
        //         'total' => {total number through this sequence}
        //         'answers' => [
        //             'question_id' => [
        //                 '{answer value}' => [
        //                     'count' => {total number who answered this value}
        //                     'percentage' => {total percentage of all answers in this pattern}
        //                 ],
        //             ],
        //             ...
        //         ],
        //     ]
        // ];
        $aggregated_results = [];

        // Load and iterate through all results.
        $results = Database::getInstance()->select('social_quiz_result', ['quiz_id' => $this->id]);
        foreach ($results as $result) {
            $decoded = json_decode($result['results'], true);
            $pattern = [];

            // Calculate the pattern.
            foreach ($decoded as $question_id => $answer) {
                $pattern[] = $question_id . ':' . $answer['set'];
            }
            $pattern = implode('-', $pattern);

            // Make sure the pattern is initialized.
            if (empty($aggregated_results[$pattern])) {
                $aggregated_results[$pattern] = [
                    'total' => 0,
                    'answers' => [],
                ];
            }

            // Increase the pattern by 1.
            $aggregated_results[$pattern]['total'] ++;

            // Add the values.
            foreach ($decoded as $question_id => $answer) {
                if (!isset($aggregated_results[$pattern]['answers'][$question_id][$answer['value']])) {
                    $aggregated_results[$pattern]['answers'][$question_id][$answer['value']]['count'] = 1;
                } else {
                    $aggregated_results[$pattern]['answers'][$question_id][$answer['value']]['count'] ++;
                }
            }
        }

        // Calculate the percentages.
        foreach ($aggregated_results as &$pattern_results) {
            foreach ($pattern_results['answers'] as &$answer) {
                foreach ($answer as &$value) {
                    $value['percent'] = 100 * $value['count'] / $pattern_results['total'];
                }
            }
        }

        Database::getInstance()->insert('social_quiz_aggregated_results', [
            'quiz_id' => $this->id,
            'time' => time(),
            'results' => json_encode($aggregated_results),
        ]);
    }
}
