<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantArrayType;
use function array_key_exists;
use function count;
use function ksort;

final class NamedArgumentsHelper
{

	public static function reorderFuncArguments(
		ParametersAcceptor $parametersAcceptor,
		FuncCall $functionCall,
	): FuncCall
	{
		return new FuncCall(
			$functionCall->name,
			self::reorderArgs($parametersAcceptor, $functionCall),
			$functionCall->getAttributes(),
		);
	}

	public static function reorderMethodArguments(
		ParametersAcceptor $parametersAcceptor,
		MethodCall $methodCall,
	): MethodCall
	{
		return new MethodCall(
			$methodCall->var,
			$methodCall->name,
			self::reorderArgs($parametersAcceptor, $methodCall),
			$methodCall->getAttributes(),
		);
	}

	public static function reorderStaticCallArguments(
		ParametersAcceptor $parametersAcceptor,
		StaticCall $staticCall,
	): StaticCall
	{
		return new StaticCall(
			$staticCall->class,
			$staticCall->name,
			self::reorderArgs($parametersAcceptor, $staticCall),
			$staticCall->getAttributes(),
		);
	}

	/**
	 * @return array<int, Arg>
	 */
	private static function reorderArgs(ParametersAcceptor $parametersAcceptor, CallLike $callLike): array
	{
		$signatureParameters = $parametersAcceptor->getParameters();
		$callArgs = $callLike->getArgs();

		if (count($callArgs) === 0) {
			return [];
		}

		$hasNamedArgs = false;
		foreach ($callArgs as $arg) {
			if ($arg->name !== null) {
				$hasNamedArgs = true;
				break;
			}
		}
		if (!$hasNamedArgs) {
			return $callArgs;
		}

		$reorderedArgs = [];
		$argumentPositions = [];
		foreach ($signatureParameters as $i => $parameter) {
			$argumentPositions[$parameter->getName()] = $i;

			if (!$parameter->isOptional()) {
				continue;
			}

			$defaultValue = $parameter->getDefaultValue();
			if ($defaultValue === null) {
				if (!$parameter->isVariadic()) {
					throw new ShouldNotHappenException('A optional parameter must have a default value');
				}
				$defaultValue = new ConstantArrayType([], []);
			}
			$reorderedArgs[$i] = new Arg(
				new TypeExpr($defaultValue),
			);
		}

		foreach ($callArgs as $i => $arg) {
			if ($arg->name === null) {
				// add regular args as is
				$reorderedArgs[$i] = $arg;
			} elseif (array_key_exists($arg->name->toString(), $argumentPositions)) {
				// order named args into the position the signature expects them
				$reorderedArgs[$argumentPositions[$arg->name->toString()]] = $arg;
			}
		}

		ksort($reorderedArgs);

		return $reorderedArgs;
	}

}