<?php

namespace SkautisIntegration\Admin;

use SkautisIntegration\Auth\SkautisGateway;
use SkautisIntegration\Auth\WpLoginLogout;
use SkautisIntegration\Auth\SkautisLogin;
use SkautisIntegration\Auth\ConnectAndDisconnectWpAccount;
use SkautisIntegration\General\Actions;
use SkautisIntegration\Utils\Helpers;

class UsersManagement {

	protected $skautisGateway;
	protected $wpLoginLogout;
	protected $skautisLogin;
	protected $connectAndDisconnectWpAccount;

	public function __construct( SkautisGateway $skautisGateway, WpLoginLogout $wpLoginLogout, SkautisLogin $skautisLogin, ConnectAndDisconnectWpAccount $connectAndDisconnectWpAccount ) {
		$this->skautisGateway                = $skautisGateway;
		$this->wpLoginLogout                 = $wpLoginLogout;
		$this->skautisLogin                  = $skautisLogin;
		$this->connectAndDisconnectWpAccount = $connectAndDisconnectWpAccount;
		$this->initHooks();
	}

	private function initHooks() {
		if ( is_admin() ) {
			add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', [
				$this,
				'setupUsersManagementPage'
			], 10 );
		}
	}

	public function setupUsersManagementPage() {
		add_submenu_page(
			SKAUTISINTEGRATION_NAME,
			__( 'Správa uživatelů', 'skautis-integration' ),
			__( 'Správa uživatelů', 'skautis-integration' ),
			'manage_options',
			SKAUTISINTEGRATION_NAME . '_usersManagement',
			[ $this, 'printChildUsers' ]
		);
	}

	public function printChildUsers() {
		if ( ! Helpers::userIsSkautisManager() ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		$result = '
		<div class="wrap">
			<h1>' . __( 'Podřízení členové', 'skautis-integration' ) . '</h1>
		';

		if ( ! $this->skautisLogin->isUserLoggedInSkautis() ) {
			$result .= '<a href="' . $this->wpLoginLogout->getLoginUrl() . '">' . __( 'Pro zobrazení obsahu je nutné se přihlásit do skautISu', 'skautis-integration' ) . '</a>';
			$result .= '
		</div>
			';
			echo $result;

			return;
		}

		$result .= '<table class="skautisUserManagementTable"><thead style="font-weight: bold;"><tr>';
		$result .= '<th>' . __( 'Jméno a příjmení', 'skautis-integration' ) . '</th><th>' . __( 'Přezdívka', 'skautis-integration' ) . '</th><th>' . __( 'ID uživatele', 'skautis-integration' ) . '<th>' . __( 'Propojení', 'skautis-integration' ) . '</th><th></th>';
		$result .= '</tr></thead ><tbody>';

		$connectedWpUsers = new \WP_User_Query( [
			'meta_query'  => [
				[
					'key'     => 'skautisUserId_' . $this->skautisGateway->getEnv(),
					'type'    => 'numeric',
					'value'   => 0,
					'compare' => '>'
				]
			],
			'count_total' => false
		] );
		$usersData        = [];
		foreach ( $users = $connectedWpUsers->get_results() as &$user ) {
			$usersData[ get_user_meta( $user->ID, 'skautisUserId_' . $this->skautisGateway->getEnv(), true ) ] = [
				'id'   => $user->ID,
				'name' => $user->display_name
			];
		}

		foreach ( $this->skautisGateway->getSkautisInstance()->UserManagement->userAll() as $user ) {
			$connected             = '';
			$trBg                  = '';
			$connectDisconnectLink = '';
			$homeUrl               = get_home_url( null, 'skautis/auth/' . Actions::DISCONNECT_ACTION );
			$nonce                 = wp_create_nonce( SKAUTISINTEGRATION_NAME . '_disconnectWpAccountFromSkautis' );
			if ( isset( $usersData[ $user->ID ] ) ) {
				$userEditLink          = get_edit_user_link( $usersData[ $user->ID ]['id'] );
				$trBg                  = 'background-color: #d1ffd1;';
				$connected             = '<a href="' . $userEditLink . '">' . $usersData[ $user->ID ]['name'] . '</a>';
				$returnUrl             = add_query_arg( SKAUTISINTEGRATION_NAME . '_disconnectWpAccountFromSkautis', $nonce, Helpers::getCurrentUrl() );
				$returnUrl             = add_query_arg( 'user-edit_php', '', $returnUrl );
				$returnUrl             = add_query_arg( 'user_id', $usersData[ $user->ID ]['id'], $returnUrl );
				$connectDisconnectLink = add_query_arg( 'ReturnUrl', urlencode( $returnUrl ), $homeUrl );
				$connectDisconnectLink = '<a href="' . $connectDisconnectLink . '" class="button">' . __( 'Odpojit', 'skautis-integration' ) . '</a>';
			} else {
				$connectDisconnectLink = '<a href="#TB_inline?width=450&height=300&inlineId=connectUserToSkautisModal" class="button thickbox">' . __( 'Propojit', 'skautis-integration' ) . '</a>';
			}
			$result .= '<tr style="' . $trBg . '"><td class="username">' . $user->DisplayName . '</td><td>&nbsp;&nbsp;(<span class="nickname">' . $user->UserName . '</span>)</td><td>&nbsp;&nbsp;<span class="skautisUserId">' . $user->ID . '</span></td><td>' . $connected . '</td><td>' . $connectDisconnectLink . '</td></tr>';
		}
		$result .= '</tbody></table>';

		echo $result;

		?>
		</div>
		<div id="connectUserToSkautisModal" class="hidden" style="max-width:400px;">
			<div class="content">
				<h3><?php _e( 'Propojení uživatele', 'skautis-integration' ); ?> <span
						id="connectUserToSkautisModal_username"></span> <?php _e( 'se skautISem', 'skautis-integration' ); ?>
				</h3>
				<h4><?php _e( 'Vyberte uživatele již registrovaného ve WordPressu', 'skautis-integration' ); ?>:</h4>
				<select id="connectUserToSkautisModal_select">
					<option><?php _e( 'Vyberte uživatele...', 'skautis-integration' ); ?></option>
					<?php
					$notConnectedWpUsers = new \WP_User_Query( [
						'meta_query'  => [
							'relation' => 'OR',
							[
								'key'     => 'skautisUserId_' . $this->skautisGateway->getEnv(),
								'compare' => 'NOT EXISTS'
							],
							[
								'key'     => 'skautisUserId_' . $this->skautisGateway->getEnv(),
								'value'   => '',
								'compare' => '='
							]
						],
						'count_total' => false
					] );
					foreach ( $notConnectedWpUsers->get_results() as $user ) {
						echo '
						<option value="' . $user->ID . '">' . $user->data->display_name . '</option>
						';
					}
					?>
				</select>
				<a id="connectUserToSkautisModal_connectLink" class="button button-primary"
				   href="<?php echo $this->connectAndDisconnectWpAccount->getConnectWpUserToSkautisUrl(); ?>"><?php _e( 'Potvrdit', 'skautis-integration' ); ?></a>
				<div>
					<em><?php _e( 'Je možné vybrat pouze ty uživatele, kteří ještě nemají propojený účet se skautISem.', 'skautis-integration' ); ?></em>
				</div>
			</div>
		</div>
		<?php
	}

}