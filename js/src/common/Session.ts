/**
 * The `Session` class defines the current user session. It stores a reference
 * to the current authenticated user, and provides methods to log in/out.
 */
import User from './models/User';
import { RequestOptions } from './Application';

export type LoginParams = {
  /**
   * The username/email
   */
  identification: string;

  /**
   * Password
   */
  password: string;
};

export default class Session {
  /**
   * The current authenticated user.
   */
  public user: User | null;

  /**
   * The CSRF token.
   */
  public csrfToken: string | null;

  constructor(user: User | null, csrfToken: string | null) {
    this.user = user;
    this.csrfToken = csrfToken;
  }

  /**
   * Attempt to log in a user.
   */
  public login(body: LoginParams, options: Omit<RequestOptions, 'url'> = {}) {
    return app.request(
      Object.assign(
        {
          method: 'POST',
          url: `${app.forum.attribute('baseUrl')}/login`,
          body,
        },
        options
      )
    );
  }

  /**
   * Log the user out.
   */
  public logout() {
    window.location.href = `${app.forum.attribute('baseUrl')}/logout?token=${this.csrfToken}`;
  }
}
