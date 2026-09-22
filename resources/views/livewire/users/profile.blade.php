<div class="user-dashboard py-5">
    <div class="container">
        <h1 class="h3 mb-4">Your profile</h1>
        <div class="row">
            <div class="col-12">
                <div class="accordion dashboard-panel mb-4" x-data="{ expanded: true }">
                    <h2 class="h5 mb-0">
                        <button id="profile-account-heading" type="button"
                            class="btn btn-block text-left p-4 d-flex align-items-center font-weight-bold"
                            @click="expanded = !expanded" :aria-expanded="expanded.toString()" aria-expanded="true"
                            aria-controls="profile-account-details">
                            <span class="profile-icon profile-icon-purple mr-3" aria-hidden="true"><i
                                    class="fa-solid fa-address-card"></i></span>
                            Your account
                            <i class="fa-solid ml-auto" :class="expanded ? 'fa-chevron-up' : 'fa-chevron-down'"
                                aria-hidden="true"></i>
                        </button>
                    </h2>
                    <div id="profile-account-details" class="px-4 pb-4" x-show="expanded" x-collapse role="region"
                        aria-labelledby="profile-account-heading">
                        <dl class="small mb-0 dashboard-account">
                            <dt class="text-muted mt-3"><span class="profile-icon profile-icon-blue mr-2"
                                    aria-hidden="true"><i class="fa-solid fa-user"></i></span>Full name</dt>
                            <dd>{{ $user->name }}</dd>
                            <dt class="text-muted mt-3"><span class="profile-icon profile-icon-purple mr-2"
                                    aria-hidden="true"><i class="fa-solid fa-at"></i></span>Username</dt>
                            <dd>{{ $user->username ?: 'Not set' }}</dd>
                            <dt class="text-muted mt-3"><span class="profile-icon profile-icon-teal mr-2"
                                    aria-hidden="true"><i class="fa-solid fa-envelope"></i></span>Email</dt>
                            <dd>{{ $user->email }}</dd>
                            <dt class="text-muted mt-3"><span class="profile-icon profile-icon-amber mr-2"
                                    aria-hidden="true"><i class="fa-solid fa-calendar-days"></i></span>Member since</dt>
                            <dd>{{ $user->created_at?->format('F Y') }}</dd>
                            <dt class="text-muted mt-3"><span class="profile-icon profile-icon-green mr-2"
                                    aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>Account status
                            </dt>
                            <dd class="mb-0"><span class="badge badge-success">{{ ucfirst($user->status) }}</span></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>