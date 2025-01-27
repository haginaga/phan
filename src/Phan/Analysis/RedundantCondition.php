<?php declare(strict_types=1);

namespace Phan\Analysis;

use ast;
use ast\Node;
use Closure;
use Phan\CodeBase;
use Phan\Issue;
use Phan\Language\Context;
use Phan\Language\UnionType;
use Phan\Parse\ParseVisitor;

/**
 * Contains miscellaneous utilities for warning about redundant and impossible conditions
 */
class RedundantCondition
{
    private const LOOP_ISSUE_NAMES = [
        Issue::RedundantCondition       => Issue::RedundantConditionInLoop,
        Issue::ImpossibleCondition      => Issue::ImpossibleConditionInLoop,
        Issue::ImpossibleTypeComparison => Issue::ImpossibleTypeComparisonInLoop,
        Issue::CoalescingNeverNull      => Issue::CoalescingNeverNullInLoop,
        Issue::CoalescingAlwaysNull     => Issue::CoalescingAlwaysNullInLoop,
    ];

    private const GLOBAL_ISSUE_NAMES = [
        Issue::RedundantCondition       => Issue::RedundantConditionInGlobalScope,
        Issue::ImpossibleCondition      => Issue::ImpossibleConditionInGlobalScope,
        Issue::ImpossibleTypeComparison => Issue::ImpossibleTypeComparisonInGlobalScope,
        Issue::CoalescingNeverNull      => Issue::CoalescingNeverNullInGlobalScope,
        Issue::CoalescingAlwaysNull     => Issue::CoalescingAlwaysNullInGlobalScope,
    ];

    /**
     * Choose a more specific issue name based on where the issue was emitted from.
     * In loops, Phan's checks have higher false positives.
     *
     * @param Node|int|float|string $node
     * @param string $issue_name
     */
    public static function chooseSpecificImpossibleOrRedundantIssueKind($node, Context $context, string $issue_name) : string
    {
        if (ParseVisitor::isConstExpr($node)) {
            return $issue_name;
        }
        if ($context->isInGlobalScope()) {
            return self::GLOBAL_ISSUE_NAMES[$issue_name] ?? $issue_name;
        }
        if ($context->isInLoop()) {
            return self::LOOP_ISSUE_NAMES[$issue_name] ?? $issue_name;
        }

        return $issue_name;
    }

    /**
     * Emit an issue. If this is in a loop, defer the check until more is known about possible types of the variable in the loop.
     *
     * @param Node|int|string|float $node
     * @param array<int,mixed> $issue_args
     * @param Closure(UnionType):bool $is_still_issue
     */
    public static function emitInstance($node, CodeBase $code_base, Context $context, string $issue_name, array $issue_args, Closure $is_still_issue) : void
    {
        if ($context->isInLoop() && $node instanceof Node && $node->kind === ast\AST_VAR) {
            $var_name = $node->children['name'];
            if (\is_string($var_name)) {
                // @phan-suppress-next-line PhanAccessMethodInternal
                $context->deferCheckToOutermostLoop(static function (Context $context_after_loop) use ($code_base, $node, $var_name, $is_still_issue, $issue_name, $issue_args, $context) : void {
                    $scope = $context_after_loop->getScope();
                    if ($scope->hasVariableWithName($var_name)) {
                        $var_type = $scope->getVariableByName($var_name)->getUnionType()->getRealUnionType();
                        if ($var_type->isEmpty() || !$is_still_issue($var_type)) {
                            return;
                        }
                    }
                    Issue::maybeEmit(
                        $code_base,
                        $context,
                        RedundantCondition::chooseSpecificImpossibleOrRedundantIssueKind($node, $context, $issue_name),
                        $node->lineno,
                        ...$issue_args
                    );
                });
                return;
            }
        }
        Issue::maybeEmit(
            $code_base,
            $context,
            RedundantCondition::chooseSpecificImpossibleOrRedundantIssueKind($node, $context, $issue_name),
            $node->lineno ?? $context->getLineNumberStart(),
            ...$issue_args
        );
    }
}
