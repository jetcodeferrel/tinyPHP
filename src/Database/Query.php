<?php
declare(strict_types=1);

namespace TinyPHP\Database;

use InvalidArgumentException;
use PDO;
use PDOStatement;

/** Intentionally small query builder. Values are always bound; identifiers are validated. */
final class Query
{
    private array $conditions = [];
    private array $bindings = [];
    private ?int $limitValue = null;
    private ?int $offsetValue = null;
    private array $order = [];

    public function __construct(private readonly PDO $pdo, private readonly string $table)
    {
        $this->identifier($table);
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        $this->identifier($column);
        $allowed = ['=', '!=', '<>', '>', '>=', '<', '<=', 'LIKE'];
        $operator = strtoupper(trim($operator));
        if (!in_array($operator, $allowed, true)) {
            throw new InvalidArgumentException('Unsupported WHERE operator.');
        }
        $key = ':w' . count($this->bindings);
        $this->conditions[] = "`{$column}` {$operator} {$key}";
        $this->bindings[$key] = $value;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->identifier($column);
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            throw new InvalidArgumentException('Sort direction must be ASC or DESC.');
        }
        $this->order[] = "`{$column}` {$direction}";
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limitValue = max(0, $limit);
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offsetValue = max(0, $offset);
        return $this;
    }

    public function get(): array
    {
        $sql = "SELECT * FROM `{$this->table}`" . $this->whereSql();
        if ($this->order) $sql .= ' ORDER BY ' . implode(', ', $this->order);
        if ($this->limitValue !== null) $sql .= ' LIMIT ' . $this->limitValue;
        if ($this->offsetValue !== null) $sql .= ' OFFSET ' . $this->offsetValue;
        return $this->run($sql)->fetchAll();
    }

    public function first(): ?array
    {
        $this->limitValue = 1;
        return $this->get()[0] ?? null;
    }

    public function insert(array $data): int
    {
        if (!$data) throw new InvalidArgumentException('Insert data cannot be empty.');
        $columns = array_keys($data);
        foreach ($columns as $column) $this->identifier((string) $column);
        $holders = [];
        $params = [];
        foreach ($data as $column => $value) {
            $key = ':i' . count($params);
            $holders[] = $key;
            $params[$key] = $value;
        }
        $cols = implode(', ', array_map(fn($c) => "`{$c}`", $columns));
        $sql = "INSERT INTO `{$this->table}` ({$cols}) VALUES (" . implode(', ', $holders) . ')';
        $this->run($sql, $params);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(array $data): int
    {
        if (!$data) throw new InvalidArgumentException('Update data cannot be empty.');
        $sets = [];
        $params = [];
        foreach ($data as $column => $value) {
            $this->identifier((string) $column);
            $key = ':u' . count($params);
            $sets[] = "`{$column}` = {$key}";
            $params[$key] = $value;
        }
        foreach ($this->bindings as $key => $value) $params[$key] = $value;
        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $sets) . $this->whereSql();
        return $this->run($sql, $params)->rowCount();
    }

    public function delete(): int
    {
        if (!$this->conditions) {
            throw new InvalidArgumentException('Refusing to delete without a WHERE condition.');
        }
        return $this->run("DELETE FROM `{$this->table}`" . $this->whereSql(), $this->bindings)->rowCount();
    }

    private function whereSql(): string
    {
        return $this->conditions ? ' WHERE ' . implode(' AND ', $this->conditions) : '';
    }

    private function run(string $sql, ?array $params = null): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params ?? $this->bindings);
        return $statement;
    }

    private function identifier(string $identifier): void
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
            throw new InvalidArgumentException('Invalid SQL identifier: ' . $identifier);
        }
    }
}
