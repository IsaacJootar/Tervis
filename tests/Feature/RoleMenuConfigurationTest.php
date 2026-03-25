<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleMenuConfigurationTest extends TestCase
{
    /**
     * @return array<int,string>
     */
    private function roleMenuFiles(): array
    {
        return [
            resource_path('menu/centralAdminMenu.json'),
            resource_path('menu/facilityAdminMenu.json'),
            resource_path('menu/dataOfficerMenu.json'),
            resource_path('menu/stateOfficerMenu.json'),
            resource_path('menu/lgaOfficerMenu.json'),
            resource_path('menu/activationsOfficerMenu.json'),
            resource_path('menu/patientMenu.json'),
        ];
    }

    public function test_role_menu_urls_point_to_registered_routes(): void
    {
        $routeUris = [];
        foreach (Route::getRoutes() as $route) {
            $routeUris[(string) $route->uri()] = true;
        }

        foreach ($this->roleMenuFiles() as $file) {
            $payload = json_decode((string) file_get_contents($file), true);
            $this->assertIsArray($payload, 'Invalid JSON in: ' . $file);
            $this->assertIsArray($payload['menu'] ?? null, 'Missing menu array in: ' . $file);

            $this->walkMenuNodes($payload['menu'], function (array $node) use ($file, $routeUris) {
                if (!isset($node['url']) || !is_string($node['url']) || trim($node['url']) === '') {
                    return;
                }

                $path = ltrim(trim($node['url']), '/');
                $this->assertArrayHasKey(
                    $path,
                    $routeUris,
                    "Menu URL '{$node['url']}' in {$file} is not registered in route list."
                );
            });
        }
    }

    public function test_role_menu_leaf_slugs_match_registered_route_names(): void
    {
        $routeNames = [];
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (is_string($name) && $name !== '') {
                $routeNames[$name] = true;
            }
        }

        foreach ($this->roleMenuFiles() as $file) {
            $payload = json_decode((string) file_get_contents($file), true);
            $this->assertIsArray($payload, 'Invalid JSON in: ' . $file);
            $this->assertIsArray($payload['menu'] ?? null, 'Missing menu array in: ' . $file);

            $this->walkMenuNodes($payload['menu'], function (array $node) use ($file, $routeNames) {
                $hasSubmenu = isset($node['submenu']) && is_array($node['submenu']) && count($node['submenu']) > 0;
                if ($hasSubmenu) {
                    return;
                }

                if (!isset($node['url']) || !is_string($node['url']) || trim($node['url']) === '') {
                    return;
                }

                $slug = $node['slug'] ?? null;
                $this->assertIsString($slug, "Leaf menu node with URL '{$node['url']}' in {$file} must define a string slug.");
                $slug = trim((string) $slug);
                $this->assertNotSame('', $slug, "Leaf menu node with URL '{$node['url']}' in {$file} has an empty slug.");
                $this->assertArrayHasKey(
                    $slug,
                    $routeNames,
                    "Leaf menu slug '{$slug}' in {$file} does not match any registered route name."
                );
            });
        }
    }

    /**
     * @param array<int,array<string,mixed>> $nodes
     */
    private function walkMenuNodes(array $nodes, callable $callback): void
    {
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }

            $callback($node);

            if (isset($node['submenu']) && is_array($node['submenu'])) {
                $this->walkMenuNodes($node['submenu'], $callback);
            }
        }
    }
}
