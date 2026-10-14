<div class="footer">
    <div class="footer-newsletter">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-12 text-center">
                    <h4>Our Newsletter</h4>
                    <p>Apply for membership to get any useful notifications!</p>
                </div>
                <div class="col-lg-6">
                    <form action="{{ url('newsletters') }}" method="post">
                        @csrf
                        <input type="email" name="email" class="email required_validation">
                        <button type="button" onclick="return formSubmit(this.form)">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
