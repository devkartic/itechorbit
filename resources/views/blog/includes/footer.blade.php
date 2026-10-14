<footer id="footer" class="footer">
    <div class="footer-top">
        <div class="container">
            <div class="row gy-4">
                <div class="col-lg-5 col-md-12 footer-info">
                    <a href="{{ url('/') }}" class="logo d-flex align-items-center">
                        <x-brand-logo />
                    </a>
                    <p>This blog is for sharing programming skills and knowledge.</p>
                    <p class="text-success">Thank you for being here <i class="fa-solid fa-face-smile"></i>.</p>
                    <div class="social-links mt-3">
                        <a href="mailto:kartic.gharami@gmail.com" class="google"><i class="bi bi-google"></i></a>
                        <a href="https://www.linkedin.com/in/kartic-gharami-436a8b181/" class="linkedin"><i
                                class="bi bi-linkedin"></i></a>
                        <a href="https://github.com/devkartic" class="github"><i class="bi bi-github"></i></a>
                        <a href="https://stackoverflow.com/u/10728921" class="stack-overflow"><i
                                class="bi bi-stack-overflow"></i></a>
                    </div>
                </div>

                <div class="col-lg-2 col-6 footer-links">
                    <h4>Useful Links</h4>
                    <ul>
                        <li><i class="bi bi-chevron-right"></i> <a href="{{ url('/') }}">Home</a></li>
                        <li><i class="bi bi-chevron-right"></i> <a href="{{ route('blogs.about') }}">About us</a></li>
                        <li><i class="bi bi-chevron-right"></i> <a href="{{ route('blogs.services') }}">Services</a>
                        </li>
                        <li><i class="bi bi-chevron-right"></i> <a href="{{ route('blogs.privacy-policies') }}">Privacy
                                & Policy</a></li>
                        <li><i class="bi bi-chevron-right"></i> <a href="{{ route('blogs.contact') }}">Contact</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-6 footer-links">
                    <h4>Our Services</h4>
                    <ul>
                        <li><i class="bi bi-chevron-right"></i> <a href="#">Web Design</a></li>
                        <li><i class="bi bi-chevron-right"></i> <a href="#">Web Development</a></li>
                        <li><i class="bi bi-chevron-right"></i> <a href="#">Web Service</a></li>
                        <li><i class="bi bi-chevron-right"></i> <a href="#">Database Design</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-12 footer-contact text-center text-md-start">
                    <h4>Contact Us</h4>
                    <div>
                        <p class="mb-0">Niketan, Gulshan</p>
                        <p class="mb-0">Dhaka, 1212</p>
                        <p>Bangladesh </p>
                        <div class="mb-0"><a href="tel:+8801747731115"><h5 class="badge bg-primary"><i
                                        class="fa-solid fa-phone"></i></h5>
                                +8801747731115<br></a></div>
                        <div><a href="mailto:kartic.gharami@gmail.com"><h5 class="badge bg-primary"><i
                                        class="fa-solid fa-envelope"></i></h5>
                                kartic.gharami@gmail.com</a></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="copyright">
            &copy; Copyright <strong><span>{{ config('app.name', 'itechorbit') }}</span></strong>. All Rights Reserved
        </div>
        <div class="credits">
            <!-- All the links in the footer should remain intact. -->
            <!-- You can delete the links only if you purchased the pro version. -->
            <!-- Licensing information: https://bootstrapmade.com/license/ -->
            <!-- Purchase the pro version with working PHP/AJAX contact form: https://bootstrapmade.com/flexstart-bootstrap-startup-template/ -->
            Designed & Developed by <a class="fw-bold" href="{{ url('/') }}">{{ config('app.name', 'itechorbit') }}</a>
        </div>
    </div>
</footer>
