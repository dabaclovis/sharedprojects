<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Users</h1>
        <button type="button" class="btn btn-primary btn-sm" wire:click="create">Add user</button>
    </div>

    @if (session()->has('users_message'))
        <div class="alert alert-success" role="status">{{ session('users_message') }}</div>
    @endif
    @error('delete') <div class="alert alert-danger" role="alert">{{ $message }}</div> @enderror

    @if ($showForm)
        <div class="card mb-4">
            <div class="card-header">{{ $form->userId ? 'Edit user' : 'Create user' }}</div>
            <form class="card-body" wire:submit="save">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="user-name">Name</label>
                        <input id="user-name" type="text" wire:model="form.name" class="form-control form-control-sm @error('form.name') is-invalid @enderror" required maxlength="255" autocomplete="name">
                        @error('form.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group col-md-6">
                        <label for="user-username">Username <small class="text-muted">(optional)</small></label>
                        <input id="user-username" type="text" wire:model="form.username" class="form-control form-control-sm @error('form.username') is-invalid @enderror" maxlength="255" autocomplete="off">
                        @error('form.username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group col-md-6">
                        <label for="user-email">Email</label>
                        <input id="user-email" type="email" wire:model="form.email" class="form-control form-control-sm @error('form.email') is-invalid @enderror" required maxlength="255" autocomplete="off">
                        @error('form.email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group col-md-6">
                        <label for="user-password">Password</label>
                        <input id="user-password" type="password" wire:model="form.password" class="form-control form-control-sm @error('form.password') is-invalid @enderror" autocomplete="new-password" minlength="8" maxlength="72" @required(!$form->userId) aria-describedby="user-password-help">
                        <small id="user-password-help" class="form-text text-muted">{{ $form->userId ? 'Leave blank to keep the current password.' : 'Use at least 8 characters.' }}</small>
                        @error('form.password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group col-md-6">
                        <label for="user-role">Role</label>
                        <select id="user-role" wire:model="form.role" class="form-control form-control-sm @error('form.role') is-invalid @enderror">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                        @error('form.role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group col-md-6">
                        <label for="user-status">Status</label>
                        <select id="user-status" wire:model.live="form.status" class="form-control form-control-sm @error('form.status') is-invalid @enderror">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                        @error('form.status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    @if ($form->status === 'suspended')
                        <div class="form-group col-12">
                            <label for="suspension-reason">Suspension reason (shown to the user)</label>
                            <textarea id="suspension-reason" class="form-control" wire:model="form.suspension_reason" maxlength="2000" required></textarea>
                            @error('form.suspension_reason')<p class="text-danger small">{{ $message }}</p>@enderror
                        </div>
                    @endif
                </div>
                <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled">Save user</button>
                <button type="button" class="btn btn-secondary btn-sm" wire:click="cancel" wire:loading.attr="disabled">Cancel</button>
            </form>
        </div>
    @endif

    <div class="form-group">
        <label for="user-search" class="sr-only">Search users</label>
        <input id="user-search" type="search" class="form-control form-control-sm" wire:model.live.debounce.300ms="search" placeholder="Search name, username or email">
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm">
            <thead class="thead-light">
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Username</th>
                    <th scope="col">Email</th>
                    <th scope="col">Role</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="align-middle">{{ $user->name }}</td>
                        <td class="align-middle">{{ $user->username ?? '—' }}</td>
                        <td class="align-middle">{{ $user->email }}</td>
                        <td class="align-middle">{{ ucfirst($user->role) }}</td>
                        <td class="align-middle"><span class="badge badge-{{ $user->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($user->status) }}</span></td>
                        <td class="text-right text-nowrap">
                            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="edit({{ $user->id }})" wire:loading.attr="disabled" aria-label="Edit {{ $user->name }}">Edit</button>
                            @if ($user->id !== auth()->id())
                                <button type="button" class="btn btn-outline-danger btn-sm" wire:click="delete({{ $user->id }})" wire:confirm="Delete this user? This cannot be undone." wire:loading.attr="disabled" aria-label="Delete {{ $user->name }}">Delete</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
</div>
