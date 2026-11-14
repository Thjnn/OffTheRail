<?php

namespace App\Exceptions;

use RuntimeException;

/** Thao tác trên đơn bị từ chối. Message bằng tiếng Việt, hiển thị thẳng cho người dùng. */
class OrderActionException extends RuntimeException {}
