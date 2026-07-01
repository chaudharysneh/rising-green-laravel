@extends('layouts.app')

@section('content')
    <div class="min-vh-100 d-flex align-items-center position-relative overflow-hidden"
        style="background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);">

        <div class="position-absolute top-0 start-0 w-100 h-100" style="z-index: 1; opacity: 0.85;">
            <svg width="100%" height="100%" viewBox="0 0 1440 320" preserveAspectRatio="none"
                style="position: absolute; bottom: 0;">

                <!-- Dark Blue Wave Layer 1 -->
                <path fill="#1e3a8a" fill-opacity="0.75"
                    d="M0,192L48,197.3C96,203,192,213,288,213.3C384,213,480,203,576,186.7C672,171,768,149,864,154.7C960,160,1056,192,1152,197.3C1248,203,1344,181,1392,170.7L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z">
                </path>

                <!-- Dark Blue Wave Layer 2 (slightly different shade) -->
                <path fill="#1e40af" fill-opacity="0.65"
                    d="M0,256L48,240C96,224,192,192,288,186.7C384,181,480,203,576,213.3C672,224,768,224,864,202.7C960,181,1056,139,1152,122.7C1248,107,1344,117,1392,122.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z">
                </path>
            </svg>
        </div>

        <div class="container position-relative" style="z-index: 2;">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-7">
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                        <div class="card-body p-5 p-lg-5 py-lg-4">

                            <!-- Header -->
                            <div class="text-center mb-1">
                                <img src="{{ asset('images/template/Fablead logo.jpg') }}" alt="Fablead CRM"
                                    style="max-width:130px;" class="img-fluid mb-2"
                                    onerror="this.onerror=null;this.src='{{ url('public/images/template/Fablead logo.jpg') }}';">
                                <p class="text-muted mb-0" style="font-size: 1rem;">Welcome to Fablead CRM, Empowering your
                                    solar business with a smart, all-in-one CRM.</p>
                            </div>

                            <div class="my-4">
                                <div class="d-flex align-items-center gap-2">
                                    <hr class="flex-grow-1 border-1 border-secondary">
                                    <span class="fs-3 px-1 text-dark-blue">Login</span>
                                    <hr class="flex-grow-1 border-1 border-secondary">
                                </div>
                            </div>

                            <form method="POST" action="{{ route('login.submit') }}" novalidate>
                                @csrf

                                <!-- Email -->
                                <div class="mb-4">
                                    <label for="email" class="form-label fw-medium">Email Address</label>
                                    <input id="email" type="email"
                                        class="form-control form-control-lg @error('email') is-invalid @enderror"
                                        name="email" value="{{ old('email') }}" required autofocus>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Password -->
                                <div class="mb-4">
                                    <label for="password" class="form-label fw-medium">Password</label>
                                    <div class="position-relative">
                                        <input id="password" type="password"
                                            class="form-control form-control-lg @error('password') is-invalid @enderror"
                                            name="password" required style="padding-right: 50px;">
                                        <button type="button"
                                            class="btn position-absolute end-0 top-50 translate-middle-y border-0 bg-transparent text-muted toggle-password"
                                            style="z-index: 10; margin-right: 10px;">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    @error('password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Forgot Password -->
                                <!-- Removed: Forgot Password functionality not implemented -->

                                <!-- Login Button -->
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-lg fw-semibold text-white"
                                        style="background: linear-gradient(135deg, #2b3a69, #182244); border: none;">Sign
                                        In</button>
                                </div>
                            </form>

                            <footer class="mt-4 text-center text-muted" style="font-size: 0.8rem;">
                                &copy; {{ date('Y') }} Copyright - Fablead Developers Technolab
                            </footer>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .card {
            border-radius: 20px !important;
        }

        .form-control {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 14px 18px;
        }

        .form-control:focus {
            border-color: #17234a;
            box-shadow: 0 0 0 4px rgba(27, 28, 77, 0.12);
        }

        .text-dark-blue {
            color: #17234a !important;
        }

        [data-theme="dark"] .text-dark-blue {
            color: #ffffff !important;
        }

        .toggle-password:focus {
            box-shadow: none !important;
            outline: none !important;
        }

        .toggle-password i {
            font-size: 1.25rem;
            transition: color 0.2s ease-in-out;
        }

        .toggle-password:hover i {
            color: #17234a !important;
        }

        [data-theme="dark"] .toggle-password:hover i {
            color: #ffffff !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const togglePassword = document.querySelector('.toggle-password');
            const passwordInput = document.querySelector('#password');

            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function () {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);

                    const icon = this.querySelector('i');
                    if (type === 'text') {
                        icon.classList.remove('bi-eye');
                        icon.classList.add('bi-eye-slash');
                    } else {
                        icon.classList.remove('bi-eye-slash');
                        icon.classList.add('bi-eye');
                    }
                });
            }
        });
    </script>
@endsection