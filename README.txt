Funcionalities
    CD:
        - change directory
        - absolute, relative, /, -, ..
    MKDIR:
        - Make Directory
        - multiple at one
        - throws if dir exists
    RM:
        - Remove Element
        - multiple at once
        - select multiple via *wildcard
        - throws if would delete higher Ranked
    CP:
        - copy element
        - prompt before replacing
    MV:
        - move element
        - rename files
        - prompt before replacingthrows if
    PWD:
        - print working directory
        - writes to stdout
    LS: 
        - list elements 
        - -l long format 
        - writes to stdout 
    GREP:
        - filter 
        - -r search recursively 
        - -i search caseInsensitive 
        - -v search not non-matches 
        - writes to stdout 
        - reads from stdin 
    ECHO:
        - print entered string 
        - writes to stdout 
    EXECUTE:
        - execute file 
    MAN:
        - get manual 
    TOUCH: 
        - create file 
        - open file 
        - update timestamp file 
    CAT: 
        - get file content
        - writes to stdout
    FIND: 
        - get all paths when no argument provided
        - filter by name 
        - search name wildcard
        - writes to stdout
    WC: 
        - count: lines, words, chars
        - -l -w -c selectors
        - reads from stdout
        - reads from stdin
    HEAD: 
        - get n(default: 10) number of lines from start file 
        - reads from stdin
    TAIL: 
        - get n(default: 10) number of lines from end of file
        - reads from stdin
    NANO:
        - open files
    |:
        - pipe commands
    &&: 
        - run multiple commands
    >>:
        - redirect output to append(>>)/overwrite(>) scrolls
    ||:
        - failsafe