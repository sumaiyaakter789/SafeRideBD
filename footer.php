<footer class="footer">
    <style>
        .footer {
            background-color: var(--bg-secondary);
            border-top: 2px solid var(--border-color);
            padding: 48px 24px 32px;
            margin-top: 60px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 32px;
        }

        .footer-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            text-align: center;
        }

        .footer-logo img {
            height: 80px;
            width: auto;
            opacity: 1;
        }

        .footer-logo-text {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .footer-tagline {
            color: var(--text-muted);
            font-size: 15px;
            max-width: 500px;
            line-height: 1.8;
        }

        .footer-links {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 16px 24px;
        }

        .footer-link {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 15px;
            transition: all 0.2s;
            padding: 6px 12px;
            border-radius: 6px;
        }

        .footer-link:hover {
            color: var(--text-primary);
            background-color: var(--bg-hover);
        }

        .footer-link i {
            margin-right: 6px;
            color: var(--accent-primary);
        }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            padding-top: 24px;
            border-top: 1px solid var(--border-color);
            flex-wrap: wrap;
            gap: 20px;
        }

        .copyright {
            color: var(--text-muted);
            font-size: 14px;
        }

        .social-links {
            display: flex;
            gap: 12px;
        }

        .social-link {
            width: 38px;
            height: 38px;
            border-radius: 6px;
            background-color: var(--bg-hover);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s;
            border: 1px solid var(--border-color);
        }

        .social-link:hover {
            background-color: var(--accent-primary);
            color: white;
            border-color: var(--accent-primary);
            transform: translateY(-3px);
        }

        .emergency-note {
            margin-top: 24px;
            font-size: 13px;
            color: var(--text-muted);
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .emergency-note i {
            color: var(--accent-danger);
        }

        @media (max-width: 768px) {
            .footer {
                padding: 40px 20px 28px;
            }
            
            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>

    <div class="footer-content">
        <div class="footer-logo">
            <img src="saferidebd_removebg_main.png" alt="SafeRideBD Logo">
            <div class="footer-logo-text">SafeRideBD</div>
            <p class="footer-tagline">ঢাকায় নিরাপদ ও সাশ্রয়ী মূল্যের পাবলিক পরিবহনের জন্য আপনার বিশ্বস্ত সঙ্গী। নির্ভরযোগ্য ভাড়া, নিরাপদ যাত্রা।</p>
        </div>
        
        <div class="footer-links">
            <a href="index.php" class="footer-link">
                <i class="fas fa-home"></i> হোম
            </a>
            <a href="#" class="footer-link">
                <i class="fas fa-info-circle"></i> আমাদের সম্পর্কে
            </a>
            <a href="#" class="footer-link">
                <i class="fas fa-phone"></i> যোগাযোগ
            </a>
            <a href="archieve.html" class="footer-link">
                <i class="fas fa-shield-alt"></i> আর্কাইভস
            </a>
            <a href="#" class="footer-link">
                <i class="fas fa-lock"></i> গোপনীয়তা নীতি
            </a>
        </div>
        
        <div class="footer-bottom">
            <div class="copyright">
                <i class="far fa-copyright"></i> <?php echo date('Y'); ?> SafeRideBD - সর্বস্বত্ব সংরক্ষিত
            </div>
            
            <div class="social-links">
                <a href="#" class="social-link" title="ফেসবুক">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" class="social-link" title="টুইটার">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="#" class="social-link" title="ইন্সটাগ্রাম">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="#" class="social-link" title="ইমেইল">
                    <i class="fas fa-envelope"></i>
                </a>
            </div>
        </div>
        
        <div class="emergency-note">
            <i class="fas fa-exclamation-triangle"></i>
            জরুরি প্রয়োজনে নেভিগেশন বারের জরুরী সেবা ব্যবহার করুন
        </div>
    </div>
</footer>

</body>
</html>