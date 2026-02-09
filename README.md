# SHELL SPELL
**A Gamified Learning Tool For The CLI**<br/>


## Introduction
Shell Spell started out as a University Project. 
My goal was to create something that enabled users of various Skill-Levels to improve their terminal abilities.
Beginners should quickly go from "What is this black Box?" to "I finally know my ways around here", 
and more experienced users from "I know my ways around here" to "I feel like the computer guys from the Matrix".

Sections:
- Challanges
- Feature Manual

## Challanges

**Parser**<br/>
Parsing the input definitely presented the biggest challange. 
At first, i wrote something that

- reads the main command (first word)
- parses paths as arrays 
- reads options

This WAS NOT able to:

- check for token (parsed arguments e.g. path, option...) structure: 
    - if token is valid for the respective command 
    - if token order is correct 
- check if option is valid
- read optional values 
- throw if excess arguments were provided
- vary token parsing/validation based on command (e.g. mkdir path argument would always be recognized as non existent -> invalid path)

This solution was of course temporary, and only existed so i could write the actual command execution logic.


My final Solution consists of class that:

- know the base command
- has default parser for each token type
- can use specific parsers
- checks for valid options
- can read the value of optional arguments
- knows if reads stdin
- knows if writes to stdout (more on those later)

This version turned out to be highly flexible and scalable, which enabled me to very easliy implement new commands.

<br>**Multicommand operators (|, ||, &&, >>, >)**<br>
Implementing these, required a new system that can execute commands consecutively and read the previous commands response (stdout) as its input (stdin).
This was achieved by recursively checking:
1. if multicommand operator is presemt in input String 
2. -> seperate input to before(first command)/after(second command) last occurence
3. -> recall execution logic (step 1) with first command 
4. -> execute second command after first

the pipe '|' operator checks if
- non-last commands writes to stdout
- non-first commands reads from stdin

## Feature Manual
    CD:
        - change directory
        - absolute, relative, /, -, ..
    MKDIR:
        - Make Directory
        - multiple at one
        - throws if dir exists
    RM:
        - Remove Item
        - multiple at once
        - select multiple via wildcard "*"
        - throws if would delete higher Ranked Item
    RMDIR: 
        - Remove Room
        - multiple at once
        - select via wildcard "*"
        - throws if would delete higher Ranked Item or Room
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
