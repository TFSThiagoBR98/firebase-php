<?php

declare(strict_types=1);

namespace Kreait\Firebase\RemoteConfig;

use JsonSerializable;
use Kreait\Firebase\Exception\InvalidArgumentException;

use function array_key_exists;
use function array_values;
use function is_array;
use function sprintf;

class Template implements JsonSerializable
{
    private string $etag = '*';

    /** @var Parameter[] */
    private array $parameters = [];

    /** @var ParameterGroup[] */
    private array $parameterGroups = [];

    /** @var Condition[] */
    private array $conditions = [];
    private ?Version $version = null;

    private function __construct()
    {
    }

    public static function new(): self
    {
        return new self();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, ?string $etag = null): self
    {
        $template = new self();
        $template->etag = $etag ?? '*';

        foreach ((array) ($data['conditions'] ?? []) as $conditionData) {
            $template = $template->withCondition(self::buildCondition($conditionData['name'], $conditionData));
        }

        foreach ((array) ($data['parameters'] ?? []) as $name => $parameterData) {
            $template = $template->withParameter(self::buildParameter($name, $parameterData));
        }

        foreach ((array) ($data['parameterGroups'] ?? []) as $name => $parameterGroupData) {
            $template = $template->withParameterGroup(self::buildParameterGroup($name, $parameterGroupData));
        }

        if (is_array($data['version'] ?? null)) {
            $template->version = Version::fromArray($data['version']);
        }

        return $template;
    }

    /**
     * @internal
     */
    public function etag(): string
    {
        return $this->etag;
    }

    /**
     * @return Condition[]
     */
    public function conditions(): array
    {
        return $this->conditions;
    }

    /**
     * @return Parameter[]
     */
    public function parameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return ParameterGroup[]
     */
    public function parameterGroups(): array
    {
        return $this->parameterGroups;
    }

    public function version(): ?Version
    {
        return $this->version;
    }

    public function withParameter(Parameter $parameter): self
    {
        $this->assertThatAllConditionalValuesAreValid($parameter);

        $template = clone $this;
        $template->parameters[$parameter->name()] = $parameter;

        return $template;
    }

    public function withParameterGroup(ParameterGroup $parameterGroup): self
    {
        $template = clone $this;
        $template->parameterGroups[$parameterGroup->name()] = $parameterGroup;

        return $template;
    }

    public function withCondition(Condition $condition): self
    {
        $template = clone $this;
        $template->conditions[$condition->name()] = $condition;

        return $template;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'conditions' => empty($this->conditions) ? null : array_values($this->conditions),
            'parameters' => empty($this->parameters) ? null : $this->parameters,
            'parameterGroups' => empty($this->parameterGroups) ? null : $this->parameterGroups,
        ];
    }

    /**
     * @param array<string, string> $data
     */
    private static function buildCondition(string $name, array $data): Condition
    {
        $condition = Condition::named($name)->withExpression($data['expression']);

        if ($tagColor = $data['tagColor'] ?? null) {
            $condition = $condition->withTagColor(new TagColor($tagColor));
        }

        return $condition;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function buildParameter(string $name, array $data): Parameter
    {
        $parameter = Parameter::named($name)
            ->withDescription((string) ($data['description'] ?? ''))
            ->withDefaultValue(DefaultValue::fromArray($data['defaultValue'] ?? []));

        foreach ((array) ($data['conditionalValues'] ?? []) as $key => $conditionalValueData) {
            $parameter = $parameter->withConditionalValue(new ConditionalValue($key, $conditionalValueData['value']));
        }

        return $parameter;
    }

    /**
     * @param array<string, mixed> $parameterGroupData
     */
    private static function buildParameterGroup(string $name, array $parameterGroupData): ParameterGroup
    {
        $group = ParameterGroup::named($name)
            ->withDescription((string) ($parameterGroupData['description'] ?? ''));

        foreach ($parameterGroupData['parameters'] ?? [] as $parameterName => $parameterData) {
            $group = $group->withParameter(self::buildParameter($parameterName, $parameterData));
        }

        return $group;
    }

    private function assertThatAllConditionalValuesAreValid(Parameter $parameter): void
    {
        foreach ($parameter->conditionalValues() as $conditionalValue) {
            if (!array_key_exists($conditionalValue->conditionName(), $this->conditions)) {
                $message = 'The conditional value of the parameter named "%s" refers to a condition "%s" which does not exist.';

                throw new InvalidArgumentException(sprintf($message, $parameter->name(), $conditionalValue->conditionName()));
            }
        }
    }
}
