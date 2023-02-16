<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

/**
 * Value binder class manages list of values bound to conditions.
 *
 * @internal
 */
class ValueBinder
{
    /**
     * Array containing a list of bound values to the conditions on this
     * object. Each array entry is another array structure containing the actual
     * bound value, its type and the placeholder it is bound to.
     *
     * @var array
     */
    protected array $_bindings = [];

    /**
     * A counter of the number of parameters bound in this expression object
     *
     * @var int
     */
    protected int $_bindingsCount = 0;

    /**
     * Associates a query placeholder to a value and a type
     *
     * @param string|int $param placeholder to be replaced with quoted version
     * of $value
     * @param mixed $value The value to be bound
     * @param string|int|null $type the mapped type name, used for casting when sending
     * to database
     * @return void
     */
    public function bind(string|int $param, mixed $value, string|int|null $type = null): void
    {
        $this->_bindings[$param] = compact('value', 'type') + [
            'placeholder' => is_int($param) ? $param : substr($param, 1),
        ];
    }

    /**
     * Creates a unique placeholder name if the token provided does not start with ":"
     * otherwise, it will return the same string and internally increment the number
     * of placeholders generated by this object.
     *
     * @param string $token string from which the placeholder will be derived from,
     * if it starts with a colon, then the same string is returned
     * @return string to be used as a placeholder in a query expression
     */
    public function placeholder(string $token): string
    {
        $number = $this->_bindingsCount++;
        if ($token[0] !== ':' && $token !== '?') {
            $token = sprintf(':%s%s', $token, $number);
        }

        return $token;
    }

    /**
     * Creates unique named placeholders for each of the passed values
     * and binds them with the specified type.
     *
     * @param iterable $values The list of values to be bound
     * @param string|int|null $type The type with which all values will be bound
     * @return array with the placeholders to insert in the query
     */
    public function generateManyNamed(iterable $values, string|int|null $type = null): array
    {
        $placeholders = [];
        foreach ($values as $k => $value) {
            $param = $this->placeholder('c');
            $this->_bindings[$param] = [
                'value' => $value,
                'type' => $type,
                'placeholder' => substr($param, 1),
            ];
            $placeholders[$k] = $param;
        }

        return $placeholders;
    }

    /**
     * Returns all values bound to this expression object at this nesting level.
     * Subexpression bound values will not be returned with this function.
     *
     * @return array
     */
    public function bindings(): array
    {
        return $this->_bindings;
    }

    /**
     * Clears any bindings that were previously registered
     *
     * @return void
     */
    public function reset(): void
    {
        $this->_bindings = [];
        $this->_bindingsCount = 0;
    }

    /**
     * Resets the bindings count without clearing previously bound values
     *
     * @return void
     */
    public function resetCount(): void
    {
        $this->_bindingsCount = 0;
    }

    /**
     * Binds all the stored values in this object to the passed statement.
     *
     * @param \Cake\Database\StatementInterface $statement The statement to add parameters to.
     * @return void
     */
    public function attachTo(StatementInterface $statement): void
    {
        $bindings = $this->bindings();
        if (empty($bindings)) {
            return;
        }

        foreach ($bindings as $b) {
            $statement->bindValue($b['placeholder'], $b['value'], $b['type']);
        }
    }
}