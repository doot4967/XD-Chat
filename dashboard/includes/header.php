<header class="xd-dashboard-header">

    <div class="xd-header-left">

        <button class="xd-sidebar-toggle">

            <i class="fa-solid fa-bars"></i>

        </button>

        <div>

            <h1>

                <?php echo $page_heading; ?>

            </h1>

            <p>

                <?php echo $page_description; ?>

            </p>

        </div>

    </div>

    <div class="xd-header-right">

        <div class="xd-header-search">

            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="text"
                placeholder="Search...">

        </div>

        <button class="xd-header-icon">

            <i class="fa-regular fa-bell"></i>

            <span>3</span>

        </button>

        <div class="xd-header-profile">

            <div class="xd-profile-avatar">

                <?php echo strtoupper(substr($_SESSION["user_name"], 0, 1)); ?>

            </div>

            <div>

                <strong>

                    <?php echo $_SESSION["user_name"]; ?>

                </strong>

                <small>

                    Administrator

                </small>

            </div>

        </div>

    </div>

</header>