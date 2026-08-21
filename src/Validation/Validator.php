<?php

declare(strict_types=1);

namespace Quillstack\Framework\Validation;

use Quillstack\Framework\Validation\Exceptions\ValidationFailedException;
use Quillstack\ValidatorInterface\ValidatorInterface;

/**
 * Checks what was sent against a list of rules per field. Everything that failed is
 * collected, so the client is told about all of it at once rather than one field at a time.
 */
class Validator implements ValidatorInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * @var array<string, array<int, RuleInterface|string>>
     */
    private array $rules = [];

    public function __construct(private readonly RuleFactory $ruleFactory)
    {
        //
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, array<int, RuleInterface|string>> $rules
     */
    public function with(array $data, array $rules): self
    {
        $validator = clone $this;
        $validator->data = $data;
        $validator->rules = $rules;

        return $validator;
    }

    /**
     * Returns only the fields there were rules for, so what reaches the application is what
     * it asked about and nothing else that happened to be sent.
     *
     * @param array<string, mixed> $data
     * @param array<string, array<int, RuleInterface|string>> $rules
     *
     * @return array<string, mixed>
     *
     * @throws ValidationFailedException
     */
    public function check(array $data, array $rules): array
    {
        $errors = $this->findErrors($data, $rules);

        if ($errors !== []) {
            throw new ValidationFailedException($errors);
        }

        return array_intersect_key($data, $rules);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, array<int, RuleInterface|string>> $rules
     *
     * @return array<string, string[]>
     */
    public function findErrors(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $rule = $this->ruleFactory->create($rule);

                if (!$rule->passes($value, $data)) {
                    $errors[$field][] = $rule->message($field);
                }
            }
        }

        return $errors;
    }

    /**
     * {@inheritDoc}
     *
     * @throws ValidationFailedException
     */
    public function validate(): bool
    {
        $this->check($this->data, $this->rules);

        return true;
    }
}
