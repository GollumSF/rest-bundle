<?php
namespace Test\GollumSF\RestBundle\Helper;

use PHPUnit\Framework\Constraint\Callback;

/**
 * Replacement for withConsecutive() removed in PHPUnit 10+
 */
trait WithConsecutiveTrait
{
	/**
	 * @param array[] $expectedCalls Array of arrays, each sub-array is the expected arguments for one call
	 * @return Callback
	 */
	public static function withConsecutiveArgs(array $expectedCalls, array $returnValues = []): array
	{
		$callIndex = 0;
		$callback = function () use (&$callIndex, $expectedCalls, $returnValues) {
			$args = func_get_args();
			if (isset($expectedCalls[$callIndex])) {
				$expected = $expectedCalls[$callIndex];
				foreach ($expected as $i => $expectedArg) {
					if (isset($args[$i])) {
						\PHPUnit\Framework\Assert::assertEquals($expectedArg, $args[$i], sprintf(
							'Call #%d, argument #%d mismatch', $callIndex + 1, $i + 1
						));
					}
				}
			}
			$returnValue = $returnValues[$callIndex] ?? null;
			$callIndex++;
			return $returnValue;
		};

		return [$callback, count($expectedCalls)];
	}
}
