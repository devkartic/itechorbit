<section id="contact" class="contact">

    <div class="container" data-aos="fade-up">

        <header class="section-header">
            <h2>Contact</h2>
            <p>Contact Me</p>
        </header>

        <div class="row gy-4">

            <div class="col-lg-6">

                <div class="row gy-4">
                    @if(!empty($user->basic))
                    <div class="col-md-6">
                        <div class="info-box">
                            <i class="bi bi-geo-alt"></i>
                            <h3>Address</h3>
                            <p>{{ $user->basic->address }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box">
                            <i class="bi bi-telephone"></i>
                            <h3>Call Me</h3>
                            <a href="tel:{{ $user->basic->mobile }}">{{ $user->basic->mobile }}</a>
                        </div>
                    </div>
                    @endif
                    <div class="col-md-6">
                        <div class="info-box">
                            <i class="bi bi-envelope"></i>
                            <h3>Email Me</h3>
                            <a href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box">
                            <i class="bi bi-clock"></i>
                            <h3>Open Hours</h3>
                            <p>Monday - Friday<br>09:00AM - 05:00PM</p>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-lg-6">
                <form action="{{ url('contacts') }}" method="post" class="php-email-form">
                    @csrf
                    <input type="hidden" name="portfolio_id" value="{{ $user->portfolio_id }}">
                    <div class="row gy-4">

                        <div class="col-md-6">
                            <input type="text" name="name" class="form-control name required_validation" placeholder="Your Name">
                        </div>

                        <div class="col-md-6 ">
                            <input type="email" class="form-control email required_validation" name="email" placeholder="Your Email">
                        </div>

                        <div class="col-md-12">
                            <input type="text" class="form-control subject required_validation" name="subject" placeholder="Subject">
                        </div>

                        <div class="col-md-12">
                            <textarea class="form-control message required_validation" name="message" rows="6" placeholder="Message"></textarea>
                        </div>

                        <div class="col-md-12 text-center">
                            <button type="button" onclick="return formSubmit(this.form)">Send Message</button>
                        </div>

                    </div>
                </form>

            </div>

        </div>

    </div>

</section>
