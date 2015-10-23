<?php
declare(strict_types=1);
namespace phan\language\element;

require_once(__DIR__.'/../Context.php');
require_once(__DIR__.'/Comment.php');

use \phan\language\Context;
use \phan\language\element\Comment;

/**
 *
 */
class StructuralElement {

    /**
     * @var \phan\Context
     * The context in which the structural element lives
     */
    private $context = null;

    /**
     * @var Comment
     * Any comment block associated with the structural
     * element
     */
    private $comment = null;

    /**
     * @param Context $context
     * The context in which the structural element lives
     *
     * @param Comment $comment,
     * Any comment block associated with the class
     */
    public function __construct(
        Context $context,
        Comment $comment
    ) {
        $this->context = $context;
        $this->comment = $comment;
    }

    /**
     * @return Context
     * The context in which this structural element exists
     */
    public function getContext() : Context {
        return $this->context;
    }

    /**
     * @return Comment
     * A possibly null comment associated with this structural
     * element.
     */
    public function getComment() : Comment {
        return $this->comment;
    }
}
