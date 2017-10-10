Change Log
1: start now accept null arg as namespace
2: default session driver is now set to file
3: autp delete session after reotate is now defaulted to false.
4: (queued) when ($session->name = val) old value is overwritten while ($session->name(val))` pushes new value to stack
5: (queued) flashes are dispatched from bottom to top.
6: A setConfigPath method has been added.
