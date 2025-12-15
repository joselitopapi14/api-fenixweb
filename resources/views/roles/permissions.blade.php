<x-app-layout>
    <x-slot name="header">
        Permisos del Rol
    </x-slot>

    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Permisos del Rol: {{ __('role.' . strtolower($role->name)) ?: $role->name }}</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">Gestiona los permisos asignados a este rol</p>
            </div>
            <a href="{{ route('roles.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-sm transition-colors duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Volver a Roles
            </a>
        </div>
    </div>

    <!-- Permissions Form -->
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl border border-gray-200 dark:border-gray-700">
        <form id="permissions-form" class="p-6">
            @csrf

            <!-- Role Info -->
            <div class="mb-6 p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg border border-primary-200 dark:border-primary-700">
                <h3 class="text-lg font-medium text-primary-900 dark:text-primary-200 mb-2">Información del Rol</h3>
                <p class="text-primary-700 dark:text-primary-300"><strong>Nombre:</strong>  {{ __('role.' . strtolower($role->name)) ?: $role->name }}</p>
                <p class="text-primary-700 dark:text-primary-300"><strong>Creado:</strong> {{ $role->created_at->format('d/m/Y H:i') }}</p>
                <p class="text-primary-700 dark:text-primary-300"><strong>Permisos actuales:</strong> {{ $role->permissions->count() }}</p>
            </div>

            <!-- Permissions by Group -->
            @if($groupedPermissions->count() > 0)
                <div class="space-y-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Seleccionar Permisos</h3>

                    @foreach($groupedPermissions as $group => $permissions)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-md font-medium text-gray-900 dark:text-white capitalize">
                                    {{ __('permission.' . $group . '.title') ?: ucfirst($group) }}
                                </h4>
                                <div class="flex space-x-2">
                                    <button type="button" onclick="selectAllInGroup('{{ $group }}')"
                                            class="text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300">
                                        Seleccionar todos
                                    </button>
                                    <button type="button" onclick="deselectAllInGroup('{{ $group }}')"
                                            class="text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-300">
                                        Deseleccionar todos
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($permissions as $permission)
                                    <label class="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                        <input type="checkbox"
                                               name="permissions[]"
                                               value="{{ $permission->name }}"
                                               data-group="{{ $group }}"
                                               {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }}
                                               class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ __('permission.' . str_replace('.', '_', $permission->name)) ?: $permission->name }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700 mt-6">
                    <button type="button" onclick="resetPermissions()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        Resetear
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        Guardar Permisos
                    </button>
                </div>
            @else
                <!-- No Permissions -->
                <div class="text-center py-12">
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No hay permisos disponibles</h3>
                    <p class="text-gray-500 dark:text-gray-400">Primero debes crear algunos permisos.</p>
                    <div class="mt-4">
                        <a href="{{ route('permissions.create') }}"
                           class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg">
                            Crear Permiso
                        </a>
                    </div>
                </div>
            @endif
        </form>
    </div>

    <script>
        // Submit form via AJAX
        document.getElementById('permissions-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const permissions = Array.from(document.querySelectorAll('input[name="permissions[]"]:checked'))
                .map(checkbox => checkbox.value);

            // Clear existing permissions array
            formData.delete('permissions[]');

            // Add selected permissions
            permissions.forEach(permission => {
                formData.append('permissions[]', permission);
            });

            fetch('{{ route("roles.sync-permissions", $role) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.toast.success(data.message || 'Permisos actualizados correctamente');
                } else {
                    throw new Error(data.message || 'Error al actualizar permisos');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                window.toast.error(error.message || 'Error al actualizar permisos');
            });
        });

        // Select all permissions in a group
        function selectAllInGroup(group) {
            document.querySelectorAll(`input[data-group="${group}"]`).forEach(checkbox => {
                checkbox.checked = true;
            });
        }

        // Deselect all permissions in a group
        function deselectAllInGroup(group) {
            document.querySelectorAll(`input[data-group="${group}"]`).forEach(checkbox => {
                checkbox.checked = false;
            });
        }

        // Reset to original permissions
        function resetPermissions() {
            // This would require storing original state, for now just reload
            location.reload();
        }
    </script>
</x-app-layout>
