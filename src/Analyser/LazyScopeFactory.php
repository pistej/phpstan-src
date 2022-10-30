<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\ShouldNotHappenException;
use function is_a;

class LazyScopeFactory implements ScopeFactory
{

	private bool $treatPhpDocTypesAsCertain;

	private bool $explicitMixedInUnknownGenericNew;

	private bool $explicitMixedForGlobalVariables;

	/**
	 * @param class-string $scopeClass
	 */
	public function __construct(
		private string $scopeClass,
		private Container $container,
	)
	{
		$this->treatPhpDocTypesAsCertain = $container->getParameter('treatPhpDocTypesAsCertain');
		$this->explicitMixedInUnknownGenericNew = $this->container->getParameter('featureToggles')['explicitMixedInUnknownGenericNew'];
		$this->explicitMixedForGlobalVariables = $this->container->getParameter('featureToggles')['explicitMixedForGlobalVariables'];
	}

	/**
	 * @param ExpressionTypeHolder[] $expressionTypes
	 * @param array<string, ConditionalExpressionHolder[]> $conditionalExpressions
	 * @param array<string, true> $currentlyAssignedExpressions
	 * @param array<string, true> $currentlyAllowedUndefinedExpressions
	 * @param ExpressionTypeHolder[] $nativeExpressionTypes
	 * @param array<(FunctionReflection|MethodReflection)> $inFunctionCallsStack
	 *
	 */
	public function create(
		ScopeContext $context,
		bool $declareStrictTypes = false,
		FunctionReflection|MethodReflection|null $function = null,
		?string $namespace = null,
		array $expressionTypes = [],
		array $conditionalExpressions = [],
		?string $inClosureBindScopeClass = null,
		?ParametersAcceptor $anonymousFunctionReflection = null,
		bool $inFirstLevelStatement = true,
		array $currentlyAssignedExpressions = [],
		array $currentlyAllowedUndefinedExpressions = [],
		array $nativeExpressionTypes = [],
		array $inFunctionCallsStack = [],
		bool $afterExtractCall = false,
		?Scope $parentScope = null,
		bool $nativeTypesPromoted = false,
	): MutatingScope
	{
		$scopeClass = $this->scopeClass;
		if (!is_a($scopeClass, MutatingScope::class, true)) {
			throw new ShouldNotHappenException();
		}

		return new $scopeClass(
			$this,
			$this->container->getByType(ReflectionProvider::class),
			$this->container->getByType(InitializerExprTypeResolver::class),
			$this->container->getByType(DynamicReturnTypeExtensionRegistryProvider::class)->getRegistry(),
			$this->container->getByType(ExprPrinter::class),
			$this->container->getByType(TypeSpecifier::class),
			$this->container->getByType(PropertyReflectionFinder::class),
			$this->container->getService('currentPhpVersionSimpleParser'),
			$this->container->getByType(NodeScopeResolver::class),
			$this->container->getByType(ConstantResolver::class),
			$context,
			$this->container->getByType(PhpVersion::class),
			$declareStrictTypes,
			$function,
			$namespace,
			$expressionTypes,
			$conditionalExpressions,
			$inClosureBindScopeClass,
			$anonymousFunctionReflection,
			$inFirstLevelStatement,
			$currentlyAssignedExpressions,
			$currentlyAllowedUndefinedExpressions,
			$nativeExpressionTypes,
			$inFunctionCallsStack,
			$this->treatPhpDocTypesAsCertain,
			$afterExtractCall,
			$parentScope,
			$nativeTypesPromoted,
			$this->explicitMixedInUnknownGenericNew,
			$this->explicitMixedForGlobalVariables,
		);
	}

}
