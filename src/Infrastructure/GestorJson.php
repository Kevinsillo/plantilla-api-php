<?php

declare(strict_types=1);

namespace Backend\Infrastructure;

class JsonRepository
{
    private string $path;
    private string $file;
    private string $filePath;

    public function __construct(string $tableName)
    {
        $this->path = ROOT_DIR . '/files/';
        $this->file = $tableName . '.json';
        $this->filePath = $this->path . $this->file;

        if (!file_exists($this->path)) {
            mkdir($this->path);
        }

        if (!file_exists($this->filePath)) {
            $this->initializeFile();
        }
    }

    private function initializeFile(): void
    {
        file_put_contents($this->filePath, json_encode(['next_id' => 1, 'data' => []]));
    }

    public function insert(array $data): int
    {
        $jsonData = file_get_contents($this->filePath);
        $tableData = json_decode($jsonData, true);

        $nextId = $tableData['next_id'];
        $data['id'] = $nextId;

        $tableData['data'][] = $data;
        $tableData['next_id']++;

        $this->saveData($tableData);

        return $nextId;
    }

    public function update(int $id, array $data): void
    {
        $jsonData = file_get_contents($this->filePath);
        $tableData = json_decode($jsonData, true);

        foreach ($tableData['data'] as &$item) {
            if ($item['id'] === $id) {
                $item = array_merge($item, $data);
                break;
            }
        }

        $this->saveData($tableData);
    }

    public function delete(int $id): void
    {
        $jsonData = file_get_contents($this->filePath);
        $tableData = json_decode($jsonData, true);

        $tableData['data'] = array_filter($tableData['data'], function ($item) use ($id) {
            return $item['id'] !== $id;
        });

        $this->saveData($tableData);
    }

    public function findById(int $id): ?array
    {
        $jsonData = file_get_contents($this->filePath);
        $tableData = json_decode($jsonData, true);

        foreach ($tableData['data'] as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }

        return null;
    }

    public function findAll(): array
    {
        $jsonData = file_get_contents($this->filePath);
        $tableData = json_decode($jsonData, true);

        return $tableData['data'];
    }

    private function saveData(array $data): void
    {
        file_put_contents($this->filePath, json_encode($data, JSON_PRETTY_PRINT));
    }
}
